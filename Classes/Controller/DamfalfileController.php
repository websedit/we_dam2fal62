<?php
namespace WE\WeDam2fal62\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Daniel Hasse - websedit AG <extensions@websedit.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * DamfalfileController
 */
class DamfalfileController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * damfalfileRepository
	 *
	 * @var \WE\WeDam2fal62\Domain\Repository\DamfalfileRepository
	 * @inject
	 */
	protected $damfalfileRepository;

	/**
	 * fileFolderRead
	 *
	 * @var \WE\WeDam2fal62\ServiceHelper\FileFolderRead
	 * @inject
	 */
	protected $fileFolderRead;

    /**
     * backendSessionHandler
     *
     * @var \WE\WeDam2fal62\ServiceHelper\BackendSession
     * @inject
     */
    protected $backendSessionHandler;

	/**
	 * action list
	 *
     * @param string $executeDamUpdateSubmit
	 * @return void
	 */
	public function listAction($executeDamUpdateSubmit = '') {
			
        $this->view->assign('tabInteger',0);

		$pathSite = $this->getRightPath();
		$this->view->assign('pathSite',$pathSite);

        // action for updating inserting the DAM-entrys from tx_dam

        // checks if there are files to import and get them; if there are no files redirect to referenceUpdateAction
		$txDamEntriesNotImported = $this->damfalfileRepository->getArrayDataFromTable('uid, file_path, file_name, sys_language_uid, l18n_parent', 'tx_dam', 'damalreadyexported <> 1 and deleted = 0', $groupBy = '', $orderBy = '', $limit = '10000');

		if ($txDamEntriesNotImported) {

            // if button was pressed start the tx_dam transfer
            if ($executeDamUpdateSubmit) {

				foreach ($txDamEntriesNotImported as $rowDamEntriesNotImported) {
						
                    // get subpart from tx_dam.file_path to compare later on with sys_file.identifier; complete it to FAL identifier
					//$completeIdentifierForFAL = $this->damfalfileRepository->getIdentifier($rowDamEntriesNotImported['file_path'],$rowDamEntriesNotImported['file_name']);

					$completeIdentifierForFAL = $this->damfalfileRepository->getIdentifier(
						$rowDamEntriesNotImported['file_path'],
						$rowDamEntriesNotImported['file_name'],
						TRUE // Make sure the imported file exists
					);
					
					$storageIdForFAL = $this->damfalfileRepository->getStorageForFile(
						$rowDamEntriesNotImported['file_path'],
						$rowDamEntriesNotImported['file_name']
					);
					// @TODO: create storage, if not exists!?

					// Make sure the imported file exists
					//if (!file_exists(PATH_site . 'fileadmin' . $completeIdentifierForFAL)) {

					if (!$completeIdentifierForFAL) {
						// if the file doesnt exist, just place the mark for imported, otherwise the unmarked file will block the process
						$this->damfalfileRepository->updateDAMTableWithFALId($rowDamEntriesNotImported['uid'], "0");
						continue;
					}
					
					// .htaccess data workaround; .htaccess data information will not be imported
					if ($rowDamEntriesNotImported['file_name'] == '.htaccess') {
						$this->damfalfileRepository->updateDAMTableWithFALId($rowDamEntriesNotImported['uid'], "0");
						continue;
					}

					// compare DAM with FAL entries in db in a foreach loop where tx_dam.file_path == sys_file.identifier and tx_dam.file_name == sys_file.name and sys_language_uid == sys_file_metadata.sys_language_uid
					//$foundFALEntry = $this->damfalfileRepository->selectOneRowQuery('file.uid', 'sys_file file, sys_file_metadata filemetadata', "file.uid = filemetadata.file AND file.identifier = '" . $this->sanitizeName($completeIdentifierForFAL) . "' AND file.name = '" . $this->sanitizeName($rowDamEntriesNotImported["file_name"]) . "' AND filemetadata.sys_language_uid = '" . $rowDamEntriesNotImported['sys_language_uid'] . "'", $groupBy = '', $orderBy = '', $limit = '10000');
					$foundFALEntry = $this->damfalfileRepository->selectOneRowQuery('file.uid', 'sys_file file, sys_file_metadata filemetadata', "file.uid = filemetadata.file AND file.identifier = '" . $this->sanitizeName($completeIdentifierForFAL) . "' AND file.name = '" . $this->sanitizeName($rowDamEntriesNotImported["file_name"]) . "' AND file.storage = '" . $storageIdForFAL . "' AND filemetadata.sys_language_uid = '" . $rowDamEntriesNotImported['sys_language_uid'] . "'", $groupBy = '', $orderBy = '', $limit = '10000');

					// if a FAL entry is found compare information and update it if necessary
					if ($foundFALEntry["uid"] > 0) {
						
						$this->damfalfileRepository->updateFALEntry($foundFALEntry['uid'], $rowDamEntriesNotImported['uid']);

					// else insert the DAM information into sys_file table
					} else {

						// check if there is a parent-entry in tx_dam for the translation
						if ($rowDamEntriesNotImported['uid'] > 0 and $rowDamEntriesNotImported['l18n_parent'] > 0 and $rowDamEntriesNotImported['sys_language_uid'] > 0) {
							
							// get information from parent entry; file_path and file_name
							$damParentFileInfo = $this->damfalfileRepository->getDamParentInformation($rowDamEntriesNotImported['l18n_parent']);

							// get subpart from tx_dam.file_path to compare later on with sys_file.identifier; complete it to FAL identifier
							$completeIdentifierForFALWithParentID = $this->damfalfileRepository->getIdentifier($damParentFileInfo['filepath'],$damParentFileInfo['filename'],TRUE);

							// compare DAM with FAL entries
							#$foundFALEntryWithParentID = $this->damfalfileRepository->selectOneRowQuery('uid', 'sys_file', "identifier = '" . addslashes($completeIdentifierForFALWithParentID) . "' and name = '" . addslashes($damParentFileInfo['filename']) . "' and sys_language_uid = '" . $rowDamEntriesNotImported['sys_language_uid'] . "'", $groupBy = '', $orderBy = '', $limit = '10000');
							
							$foundFALEntryWithParentID = $this->damfalfileRepository->selectOneRowQuery('file.uid',
							'sys_file file, sys_file_metadata filemetadata',
							//"file.uid = filemetadata.file AND file.identifier = '" . addslashes($completeIdentifierForFALWithParentID) . "' AND file.name = '" . addslashes($damParentFileInfo['filename']) . "' and filemetadata.sys_language_uid = '" . $rowDamEntriesNotImported['sys_language_uid'] . "'",
							"file.uid = filemetadata.file AND file.identifier = '" . addslashes($completeIdentifierForFALWithParentID) . "' AND file.name = '" . addslashes($damParentFileInfo['filename']) . "' AND filemetadata.sys_language_uid = '" . $rowDamEntriesNotImported['sys_language_uid'] . "'",						
							$groupBy = '',
							$orderBy = '',
							$limit = '10000');

							// if a FAL entry is found compare information and update it if necessary
							if ($foundFALEntryWithParentID['uid'] > 0) {
								// update sys_file_metadata entry
								$this->damfalfileRepository->updateFALEntryWithParent($foundFALEntryWithParentID['uid'], $rowDamEntriesNotImported['uid'], $rowDamEntriesNotImported['l18n_parent']);
							} else {
								// if a file entry exits but there is no filemetadata entry
								// test if a fal entry exists, if so then just do a filemetadata entry
								//$foundFALEntryChecked = $this->damfalfileRepository->selectOneRowQuery('uid', 'sys_file', "identifier = '" . addslashes($completeIdentifierForFALWithParentID) . "' and name = '" . addslashes($damParentFileInfo['filename']) . "'", $groupBy = '', $orderBy = '', $limit = '10000');
								$foundFALEntryChecked = $this->damfalfileRepository->selectOneRowQuery('uid', 'sys_file', "identifier = '" . addslashes($completeIdentifierForFALWithParentID) . "' and name = '" . addslashes($damParentFileInfo['filename']) . "'", $groupBy = '', $orderBy = '', $limit = '10000');

								// if a FAL entry is found, insert metadata
								if ($foundFALEntryChecked['uid'] > 0 && $rowDamEntriesNotImported['sys_language_uid'] > 0) {
									$this->damfalfileRepository->insertFALEntryMetadata($foundFALEntryChecked['uid'], $rowDamEntriesNotImported['uid'], $rowDamEntriesNotImported['l18n_parent']);
								} else {
									// update sotrage index should insert all files, so just update
									//$this->damfalfileRepository->insertFalEntry($rowDamEntriesNotImported['uid']);
									$foundFALEntryWhichHasNoFilemetadata = $this->damfalfileRepository->selectOneRowQuery('uid', 'sys_file', "identifier = '" . $this->sanitizeName($completeIdentifierForFAL) . "' AND name = '" . $this->sanitizeName($rowDamEntriesNotImported["file_name"]) . "'", $groupBy = '', $orderBy = '', $limit = '10000');
									$this->damfalfileRepository->updateFALEntry($foundFALEntryWhichHasNoFilemetadata['uid'], $rowDamEntriesNotImported['uid']);
								}
							}

						} else {
							// check if a fal entry exists but has no filemetadata entry
							// search for fal entry, comparing identifier and name
							$foundFALEntryWhichHasNoFilemetadata = $this->damfalfileRepository->selectOneRowQuery('uid', 'sys_file', "identifier = '" . $this->sanitizeName($completeIdentifierForFAL) . "' AND name = '" . $this->sanitizeName($rowDamEntriesNotImported["file_name"]) . "'", $groupBy = '', $orderBy = '', $limit = '10000');
							// if a fal entry was found take that uid otherwise insert fal entry
							if ($foundFALEntryWhichHasNoFilemetadata){
								// update fal entry
								$this->damfalfileRepository->updateFALEntry($foundFALEntryWhichHasNoFilemetadata['uid'], $rowDamEntriesNotImported['uid']);
								// create filemetadata entry
								$this->damfalfileRepository->insertFALEntryMetadata($foundFALEntryWhichHasNoFilemetadata['uid'], $rowDamEntriesNotImported['uid'], $rowDamEntriesNotImported['l18n_parent']);
							} else {
								// update sotrage index should insert all files, so just update
								// nothing should happen, because it should find sth before
								//$this->damfalfileRepository->insertFalEntry($rowDamEntriesNotImported['uid']);
							}
						}
					}
                }
	            // Handle frontend group permission
				$this->damfalfileRepository->migrateFrontendGroupPermissions();
				$this->redirect('list', NULL, NULL, NULL, NULL);
            }
        } else {
            $this->redirect('referenceUpdate', NULL, NULL, NULL, NULL);
        }
		// get data for progress information
        $txDamEntriesProgressArray = $this->damfalfileRepository->getProgressArray('tx_dam', "damalreadyexported = '1'", '');
        $this->view->assign('txDamEntriesProgressArray', $txDamEntriesProgressArray);
	}
	
	/**
	 * @param string $name
	 * @return string
	 */
	public function sanitizeName($name) {
		return addslashes(stripslashes($name));
	}

    /**
     * action referenceUpdate
	 *
     * @param integer $tabInteger
     * @param array $fieldnameToTablenameArray
     * @param string $executeTablenameMultiselect
     * @param string $chosenTablenames
	 * @param string $executeReferenceUpdateSubmit
	 * @param string $chosenExtension
	 * @param array $identifierArray
	 * @param string $executeReferenceUpdateIdentifierSubmit
	 * @param string $executeTTContentTestSubmit
	 * @param string $thumbnailTest
     * @param string $rteFilelinkTest
	 * @param string $pictureFilelinkTest
     * @return void
     */
    public function referenceUpdateAction($tabInteger = 0, array $fieldnameToTablenameArray = array(), $executeTablenameMultiselect = '', $chosenTablenames = '', $executeReferenceUpdateSubmit = '', $chosenExtension = '', array $identifierArray = array(), $executeReferenceUpdateIdentifierSubmit = '', $executeTTContentTestSubmit = '', $thumbnailTest = '', $rteFilelinkTest = '', $pictureFilelinkTest = '') {

		// sets up the integer parameter for the tabs navigation
        $tabInteger = $this->backendSessionHandler->setOrGetSessionParameter($tabInteger, 'tabInteger');
        if ($tabInteger == '' or $tabInteger == 0) {
			$tabInteger = 0;
		}
        $this->view->assign('tabInteger', $tabInteger);

		// $identifierArray 0=chosenTablename, 1=damIdentifier, 2=FALIdentifier, 3=checkboxValue, 4 = damTablename

		$pathSite = $this->getRightPath();
		$this->view->assign('pathSite', $pathSite);

        // action for updating inserting DAM-references from tx_dam_mm_ref; flag is dammmrefalreadyexported
        $chosenExtension = $this->backendSessionHandler->setOrGetSessionParameter($chosenExtension, 'chosenExtension');
		$this->view->assign('chosenExtension',$chosenExtension);

        // action for updating counted sys_file_reference entries to update the given table in the database
        $chosenTablenames = $this->backendSessionHandler->setOrGetSessionParameter($chosenTablenames, 'chosenTablenames');
        $this->view->assign('chosenTablenames', $chosenTablenames);

		$errorMarker = 0;
		// sys_file_reference should be empty in the beginning, so just insert references
		if ($executeReferenceUpdateIdentifierSubmit) {

			$errorMessageArray = array();
			$errorMarker = 1;
			$counter = 0;

			// check the empty fal inputs, these values will not be imported
			foreach ($identifierArray as $key => $value) {
				// check if checkbox was checked, if yes then do not import but update tx_dam_mm_ref entries with dammmrefnoexportwanted = 1
				if ($value[3] == 'isChecked') {

					// set dammmrefnoexportwanted to 1 referring to given ident
					$this->damfalfileRepository->updateDAMMMRefTableWithNoImportWanted($value[1]);

				} else {

					if ($value[2] != '' || $value[2] != 0) {

							// check if source was deleted, if yes do not copy
							// $mmRefInfo = $this->damfalfileRepository->getArrayDataFromTable("*", "tx_dam_mm_ref", "tablenames = '".$value[0]."' and ident = '".$value[1]."' and dammmrefalreadyexported != 1", $groupBy='', $orderBy='', $limit='10000');
							$mmRefInfo = $this->damfalfileRepository->getArrayDataFromTable('*', 'tx_dam_mm_ref', "tablenames = '" . $value[4] . "' and ident = '" . $value[1] . "' and dammmrefalreadyexported != 1", $groupBy = '', $orderBy = '', $limit = '10000');
							
							
							#$this->debug($mmRefInfoSSS);
							#$this->debug($value);
							
							
							foreach ($mmRefInfo as $rowMmRefInfo) {

								// check foreign reference -> tablename
								$fields = 'uid';

								if (!empty($GLOBALS['TCA'][$rowMmRefInfo['tablenames']]['ctrl']['languageField'])) {
									$fields .= ',sys_language_uid';
								}
								$existingReferenceForeign = $this->damfalfileRepository->selectOneRowQuery($fields, $rowMmRefInfo['tablenames'], "uid = '" . $rowMmRefInfo['uid_foreign'] . "' and deleted != 1");

								if (!empty($GLOBALS['TCA'][$rowMmRefInfo['tablenames']]['ctrl']['languageField'])) {
								    $existingReferenceForeign["sys_language_uid"] = 0;
								}
								if ($existingReferenceForeign) {

									// check local reference -> tx_dam
									$existingReferenceLocal = $this->damfalfileRepository->selectOneRowQuery('falUid', 'tx_dam', "uid = '" . $rowMmRefInfo['uid_local'] . "' and deleted != 1 and falUid != 0");
									if ($existingReferenceLocal){

										if ($existingReferenceLocal['falUid'] != '' and $existingReferenceLocal['falUid'] > 0) {

											// check if there is an existing entry in the sys_file_reference comparing with sys_language_uid, just to be sure
											// to see if there is already a reference; compare sys_file_reference.uid_local == getSysFileUid and sys_file_reference.uid_foreign == tx_dam_mm_ref.uid_foreign and sys_file_reference.tablename == tx_dam_mm_ref.tablename and sys_file_reference.sys_language_uid == getTheRightLangUid
											// $existingSysFileReference = $this->damfalfileRepository->selectOneRowQuery("uid", "sys_file_reference", "uid_foreign = '".$rowMmRefInfo["uid_foreign"]."' and uid_local = '".$existingReferenceLocal["falUid"]."' and sys_language_uid = '".$existingReferenceForeign["sys_language_uid"]."' and tablenames = '".$value[0]."' and fieldname = '".$value[1]."'");
											if ($value[4] != $value[0]) {
												$tablenameGiven = $value[0];
											} else {
												$tablenameGiven = $value[4];
											}
											$existingSysFileReference = $this->damfalfileRepository->selectOneRowQuery('uid', 'sys_file_reference', "uid_foreign = '" . $rowMmRefInfo['uid_foreign'] . "' and uid_local = '" . $existingReferenceLocal['falUid'] . "' and sys_language_uid = '" . $existingReferenceForeign['sys_language_uid'] . "' and tablenames = '" . $tablenameGiven . "' and fieldname = '" . $value[1] . "'");
											if($existingSysFileReference) {
												// update, just for tt_content
												$this->damfalfileRepository->updateSysFileReference($existingSysFileReference['uid'], $rowMmRefInfo['uid_foreign'], $tablenameGiven, $rowMmRefInfo['uid_local'], $value[1]);
											} else {
												// insert
												$this->damfalfileRepository->insertSysFileReference($existingReferenceLocal['falUid'], $rowMmRefInfo['uid_foreign'], $tablenameGiven, $value[2], $existingReferenceForeign['sys_language_uid'], $rowMmRefInfo['uid_local'], $value[1], $rowMmRefInfo['tablenames'], $rowMmRefInfo['ident']);
											}
										} else {
											$errorMessageArray[$counter]['message'] = 'noFALIdWasFoundInDAMTable';
											$errorMessageArray[$counter]['tablename'] = $value[4] . ' ' . $value[0];
											$errorMessageArray[$counter]['identifier'] = $value[1] . ' ' . $value[2];
											$errorMessageArray[$counter]['uid_foreign'] = $rowMmRefInfo['uid_foreign'];
											$errorMessageArray[$counter]['uid_local'] = $rowMmRefInfo['uid_local'];
											$errorMarker = 2;
										}
									} else {
										$errorMessageArray[$counter]['message'] = 'noLocalSourceFound or FALUid is 0';
										$errorMessageArray[$counter]['tablename'] = $value[4] . ' ' . $value[0];
										$errorMessageArray[$counter]['identifier'] = $value[1] . ' ' . $value[2];
										$errorMessageArray[$counter]['uid_foreign'] = $rowMmRefInfo['uid_foreign'];
										$errorMessageArray[$counter]['uid_local'] = $rowMmRefInfo['uid_local'];
										$errorMarker = 2;
									}
								} else {
									$errorMessageArray[$counter]['message'] = 'noForeignSourceFound';
									$errorMessageArray[$counter]['tablename'] = $value[4] . ' ' . $value[0];
									$errorMessageArray[$counter]['identifier'] = $value[1] . ' ' . $value[2];
									$errorMessageArray[$counter]['uid_foreign'] = $rowMmRefInfo['uid_foreign'];
									$errorMessageArray[$counter]['uid_local'] = $rowMmRefInfo['uid_local'];
									$errorMarker = 2;
								}
							}
					} else {
						$errorMessageArray[$counter]['message'] = 'noFALValueFilled';
						$errorMessageArray[$counter]['tablename'] = $value[4] . ' ' . $value[0];
						$errorMessageArray[$counter]['identifier'] = $value[1] . ' ' . $value[2];
						$errorMarker = 2;
					}
					$counter++;
				}
			}
		}

		if ($executeReferenceUpdateSubmit) {

			$mmRefTablenames = $this->damfalfileRepository->getArrayDataFromTable('*', 'tx_dam_mm_ref', "dammmrefnoexportwanted != 1 AND dammmrefalreadyexported != 1 AND tablenames LIKE '%" . $chosenExtension . "%'", $groupBy = 'ident, tablenames', $orderBy = '', $limit = '10000');
			// get idents, tablenames
			$damIdents = array();

            foreach ($mmRefTablenames as $rowMmRefTablenames) {
				// fill array with std values for tt_content and pages
				if ($rowMmRefTablenames['tablenames'] == 'tt_content') {
					if ($rowMmRefTablenames['ident'] == 'tx_damttcontent_files') {
						$stdValueForFALIdentifier = 'image';
					} elseif ($rowMmRefTablenames['ident'] == 'tx_damfilelinks_filelinks') {
						$stdValueForFALIdentifier = 'media';
					} else {
						//$stdValueForFALIdentifier = '';
						$stdValueForFALIdentifier = $rowMmRefTablenames['ident'];
					}
				} elseif ($rowMmRefTablenames['tablenames'] == 'pages') {
					if ($rowMmRefTablenames['ident'] == 'tx_dampages_files') {
						$stdValueForFALIdentifier = 'media';
					} else {
						//$stdValueForFALIdentifier = '';
						$stdValueForFALIdentifier = $rowMmRefTablenames['ident'];
					}
				} else {
					//$stdValueForFALIdentifier = '';
					$stdValueForFALIdentifier = $rowMmRefTablenames['ident'];
				}
				$damIdents[] = array($rowMmRefTablenames['tablenames'], $rowMmRefTablenames['ident'], $stdValueForFALIdentifier);
			}

			$countedRelationsTotal = $this->damfalfileRepository->getArrayDataFromTable('COUNT(*) AS countedNumber', 'tx_dam_mm_ref', "dammmrefnoexportwanted != 1 AND dammmrefalreadyexported != 1 AND tablenames LIKE '%" . $chosenExtension . "%'", $groupBy = '', $orderBy = '', $limit = '100000');

			$this->view->assign('countedRelationsTotal', $countedRelationsTotal[1]['countedNumber']);
			$this->view->assign('damIdents', $damIdents);

		}

		// save in an array the given identifiers for FAL the user sets up in the backend module, key is the tx_dam_mm_ref.ident
		// create select field
		$extensionNameUnique = $this->damfalfileRepository->getExtensionNamesForMultiselect();
		$this->view->assign('extensionNames', $extensionNameUnique);

		// get data for progress information
        $txDamEntriesProgressArray = $this->damfalfileRepository->getProgressArray('tx_dam', "damalreadyexported = '1'",'');
        $this->view->assign('txDamEntriesProgressArray', $txDamEntriesProgressArray);

		// write error log if necessary
		if ($errorMessageArray and $errorMarker == 2) {
			$this->fileFolderRead->writeLog($chosenExtension,$errorMessageArray,'');
		}

		$this->view->assign('errors', $errorMessageArray);
		$this->view->assign('errorMarker', $errorMarker);

		// get filename from Logs folder to create download buttons
		$folderFilenamesLog = $this->fileFolderRead->getFolderFilenames(PATH_site.'typo3conf/ext/we_dam2fal62/Logs/');

		$this->view->assign('folderFilenamesLog', $folderFilenamesLog);

		// check tt_content table
		// check if all tt_content data from tx_dam_mm_ref is already imported into fal
		$ttContentCheck = $this->damfalfileRepository->getArrayDataFromTable('Count(uid_local) AS countedrows', 'tx_dam_mm_ref', "dammmrefnoexportwanted != 1 AND dammmrefalreadyexported != 1 AND tablenames LIKE 'tt_content'", $groupBy = '', $orderBy = '', $limit = '');

		if ($ttContentCheck[1]['countedrows'] == 0) {
			$this->view->assign('ttContentCheck', $ttContentCheck);
		}

		if ($executeTTContentTestSubmit) {

			if ($thumbnailTest) {

				$countedImageMediaArray = array();

				// get all sys_file_references with tablename tt_content
				$ttContentEntriesInFileReference = $this->damfalfileRepository->getArrayDataFromTable('*', 'sys_file_reference', "tablenames = 'tt_content' AND (fieldname = 'image' OR fieldname = 'media') and table_local = 'sys_file' AND deleted <> 1", $groupBy = '', $orderBy = '', $limit = '');

				foreach ($ttContentEntriesInFileReference as $value) {
					if ($value['fieldname'] == 'image') {
						$countedImageMediaArray[$value['uid_foreign']]['image'] = $countedImageMediaArray[$value['uid_foreign']]['image'] + 1;
					}
					if ($value['fieldname'] == 'media') {
						$countedImageMediaArray[$value['uid_foreign']]['media'] = $countedImageMediaArray[$value['uid_foreign']]['media'] + 1;
					}
					// if ($value['fieldname'] == 'image'){$countedImageMediaArray[$value['uid_foreign']]['image'] = 0;}
					// if ($value['fieldname'] == 'media'){$countedImageMediaArray[$value['uid_foreign']]['media'] = 0;}
				}

				foreach ($countedImageMediaArray as $keyCounted => $imageOrMediaValueArray) {
					$fieldarray = array();
					foreach ($imageOrMediaValueArray as $key => $imageOrMediaValue) {
						$fieldarray = array(
							$key => $imageOrMediaValue
						);
					}
					$this->damfalfileRepository->updateTableEntry('tt_content', "uid = '" . $keyCounted . "'", $fieldarray);
				}
			}

			if ($rteFilelinkTest) {

				$ttContentEntriesBodytext = $this->damfalfileRepository->getArrayDataFromTable('uid, bodytext', 'tt_content', 'deleted <> 1 AND bodytext IS NOT NULL', $groupBy = '', $orderBy = '', $limit = '');

				foreach ($ttContentEntriesBodytext as $bodytextValue) {

					$falLinkBodytext = $bodytextValue['bodytext'];
					
					preg_match_all("/<media ([0-9]{1,})/",$falLinkBodytext,$matches);
					foreach ($matches[1] as $match) {
						$rowDamInfo = $this->damfalfileRepository->selectOneRowQuery('falUid', 'tx_dam', "uid = '" . $match . "'");
						$falLinkBodytext = str_replace('<media ' . $match, '<media ' . $rowDamInfo['falUid'], $falLinkBodytext);
					}
					
					$falLinkBodytext = str_replace('<media ', '<link file:', $falLinkBodytext);
					$falLinkBodytext = str_replace('</media>', '</link>', $falLinkBodytext);
					// $falLinkBodytext = str_replace('<link file:', '<media ', $falLinkBodytext);
					// $falLinkBodytext = str_replace('</link>', '</media>', $falLinkBodytext);

					$fieldsValues = array();
					$fieldsValues = array(
						'bodytext' => $falLinkBodytext
					);

					$this->damfalfileRepository->updateTableEntry('tt_content', "uid = '" . $bodytextValue['uid'] . "'", $fieldsValues);
				}

			}
			
			if ($pictureFilelinkTest) {

				$sysFileReferenceEntriesLinks = $this->damfalfileRepository->getArrayDataFromTable('uid, link', 'sys_file_reference', 'deleted <> 1 AND hidden <> 1 AND link IS NOT NULL', $groupBy = '', $orderBy = '', $limit = '');

				foreach ($sysFileReferenceEntriesLinks as $linkValue) {

					$falLinkSysFileReferenceLink = $linkValue['link'];
					
					preg_match_all("/media:([0-9]{1,})/",$falLinkSysFileReferenceLink,$matches);
					
					foreach ($matches[1] as $match) {
						$rowDamInfo = $this->damfalfileRepository->selectOneRowQuery('falUid', 'tx_dam', "uid = '" . $match . "'");
						$falLinkSysFileReferenceLink = str_replace('media:' . $match, 'media:' . $rowDamInfo['falUid'], $falLinkSysFileReferenceLink);
					}
					
					$falLinkSysFileReferenceLink = str_replace('media', 'file', $falLinkSysFileReferenceLink);

					$fieldsValues = array();
					$fieldsValues = array(
						'link' => $falLinkSysFileReferenceLink
					);

					$this->damfalfileRepository->updateTableEntry('sys_file_reference', "uid = '" . $linkValue['uid'] . "'", $fieldsValues);
				}

			}
		}

		// check if dam categories, tx_dam_cat, table is available
		$txDamCatExist = $this->damfalfileRepository->tableOrColumnFieldExist('tx_dam_cat','table','');

		if ($txDamCatExist == TRUE) {
			// category interface generation
			// get data for progress category
			$categoryProgressArray = $this->damfalfileRepository->getProgressArray('tx_dam_cat', "damcatalreadyexported = '1'",'');

			$categoryReferenceProgressArray = $this->damfalfileRepository->getProgressArray('tx_dam_mm_cat', "dammmcatalreadyexported = '1'", '');

			$this->view->assign('categoryProgressArray', $categoryProgressArray);
			$this->view->assign('categoryReferenceProgressArray', $categoryReferenceProgressArray);
		}

        // dropdown for multiselect tablenames from sys_file_reference and exec db tables with given parameters
        $tablenamesForMultiselect = $this->damfalfileRepository->getTablenamesForMultiselect();
        $this->view->assign('tablenamesForMultiselect', $tablenamesForMultiselect);
        if ($executeTablenameMultiselect) {
            // get fieldnames by chosen tablename from sys_file_reference
            $fieldnamesFromTablenames = $this->damfalfileRepository->getArrayDataFromTable('fieldname', 'sys_file_reference', "tablenames = '" . $chosenTablenames . "'", $groupBy = 'fieldname', $orderBy = '', $limit = '');
            $this->view->assign('fieldnamesFromTablenames', $fieldnamesFromTablenames);
        }
        // updates database foreign table columns with given parameters
        if ($fieldnameToTablenameArray) {
            foreach ($fieldnameToTablenameArray as $keyFieldnameToTablename => $valueFieldnameToTablename) {
                if ($valueFieldnameToTablename[2] != 'isChecked') {
                    if ($valueFieldnameToTablename[1]) {
                        // count sys_file_reference entries with given identifier sorted by foreign_uid
                        // get foreign_uid from sys_file_reference
                        $this->damfalfileRepository->getCountedUidForeignsFromSysFileReference($valueFieldnameToTablename[0], $chosenTablenames, $valueFieldnameToTablename[1]);
                    } else {
                        // no valueFieldnameToTablename given
                    }
                }
            }
        }
    }
	
	/**
     * Debug Funktion
     * @param type $value
     */
    private function debug($value) {
        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($value);
    }


	/**
     * action updateCategory
	 *
	 * @param string $executeCategoryUpdateSubmit
     * @return void
     */
	public function updateCategoryAction($executeCategoryUpdateSubmit = '') {

		if ($executeCategoryUpdateSubmit) {
			// insert all non imported categories
			$this->damfalfileRepository->insertCategory();
		}

		$arguments = array('tabInteger' => 3);

		$this->redirect('referenceUpdate', NULL, NULL, $arguments, NULL);
	}

	/**
     * function to get server path
	 * @return string
     */
	public function getRightPath() {
		$pathSite = str_replace($_SERVER['DOCUMENT_ROOT'], '', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('we_dam2fal62'));
		$pathSite = $_SERVER['HTTP_HOST'] . '/' . $pathSite;
		// $pathSite = $_SERVER['HTTP_HOST'] . $pathSite;
		return $pathSite;
	}

}