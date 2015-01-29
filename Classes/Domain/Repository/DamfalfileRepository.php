<?php
namespace WE\WeDam2fal62\Domain\Repository;


/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014
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
 * The repository for Damfalfiles
 */
class DamfalfileRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	public function countTableEntries($field, $table, $where, $groupBy = '', $orderBy = '', $limit = '') {
        $countedEntries = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'COUNT(' . $field . ') AS countedentry',
            $table,
            $where, // where-clause
            $groupBy, // group by
            $orderBy, // order by
            $limit // limit
        );
        $numberCounted = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($countedEntries);
        $number = $numberCounted['countedentry'];
        return $number;
    }

	/**
	 * Migrate Frontend Groups permissions.
	 *
	 * @return void
	 */
	public function migrateFrontendGroupPermissions() {
		// populate fe_groups permission
		$damEntries = $this->getArrayDataFromTable('uid, falUid, fe_group', 'tx_dam', 'deleted = 0 and fe_group != "0" and fe_group != ""');

		foreach ($damEntries as $entry) {
			$groups = explode(',', $entry['fe_group']);

			// First delete all references.
			$GLOBALS['TYPO3_DB']->sql_query('DELETE FROM sys_file_fegroups_mm WHERE uid_local = ' . $entry['falUid']);
			foreach ($groups as $group) {

				$GLOBALS['TYPO3_DB']->exec_INSERTquery(
					'sys_file_fegroups_mm',
					array(
						'uid_local' => $entry['falUid'],
						'uid_foreign' => $group,
					),
					$no_quote_fields = FALSE
				);
			}
		}
	}

	public function updateTableEntry($table, $where, $fieldarray) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery (
			$table,
			$where,
			$fieldarray,
			$no_quote_fields = FALSE
		);
	}

	public function tableOrColumnFieldExist($table, $const, $columnFieldname = 'uid_local') {

		if ($const != 'table') {
			$result = $GLOBALS['TYPO3_DB']->sql_query("SHOW COLUMNS FROM " . $table . " LIKE '" . $columnFieldname . "'");
		} else {
			$result = $GLOBALS['TYPO3_DB']->sql_query("SHOW TABLES LIKE '" . $table . "'");
		}

		// if table exists return true
		$res = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
		if ($res == 1) {
			$boolean = TRUE;
		} else {
			$boolean = FALSE;
		}
		return $boolean;
	}

	public function insertCategory() {

		$categories = $this->getArrayDataFromTable('*', 'tx_dam_cat', 'damcatalreadyexported != 1', $groupBy = '', $orderBy = '', $limit = '1000');

		foreach ($categories as $rowCategories) {
			// parent_id, title, nav_title, subtitle, keywords, description
			$fieldsValues = array();

			// check which category is parent category
			if ($rowCategories['parent_id'] > 0) {

				// check faluid from parent
				$falUidRow = $this->selectOneRowQuery('falCatUid', 'tx_dam_cat', "uid = '" . $rowCategories['parent_id'] . "'", '', '', '');

				if ($falUidRow['falCatUid'] > 0) {
					$parentcategory = $falUidRow['falCatUid'];
				} else {
					$parentcategory = 0;
				}

			} else {
				$parentcategory = 0;
			}

			$fieldsValues = array(
				'deleted' => $rowCategories['deleted'],
				//'sorting' => $rowCategories['sorting'],
				't3_origuid' => '0',
				'title' => $rowCategories['title'],
				'description' => $rowCategories['description'],
				'parent' => $parentcategory,
				'pid' => $rowCategories['pid'],
				'damCatUid' => $rowCategories['uid'],
				'hidden' => '0'
			);

			$GLOBALS['TYPO3_DB']->exec_INSERTquery (
				'sys_category',
				$fieldsValues,
				$no_quote_fields = FALSE
			);

			// get last inserted uid from sys_category table
			$lastInsertedIDForFAL = $GLOBALS['TYPO3_DB']->sql_insert_id();

			$this->updateDAMCategoryTableWithFALId($rowCategories['uid'], $lastInsertedIDForFAL);
		}

		$categoriesReferences = $this->getArrayDataFromTable('*', 'tx_dam_mm_cat', 'dammmcatalreadyexported != 1', $groupBy = '', $orderBy = '', $limit = '');

		foreach ($categoriesReferences as $value) {

			// get falUid from tx_dam and then get right sys_file_metadata uid
			$row = $this->selectOneRowQuery('falUid, sys_language_uid', 'tx_dam', "uid='" . $value['uid_local'] . "'", $groupBy = '', $orderBy = '', $limit = '');
			
			$rowMetadataInfo = $this->selectOneRowQuery('uid', 'sys_file_metadata', "file='" . $row['falUid'] . "' AND sys_language_uid = '" . $row['sys_language_uid'] . "'", $groupBy = '', $orderBy = '', $limit = '');
			$falMetadataUid = $rowMetadataInfo['uid'];

			// get falCatUid from tx_dam_cat
			$row = $this->selectOneRowQuery('falCatUid', 'tx_dam_cat', "uid='" . $value['uid_foreign'] . "'", $groupBy = '', $orderBy = '', $limit = '');
			$falCatUid = $row['falCatUid'];
			
			$fieldsValuesCatRef = array();
			$fieldsValuesCatRef = array(
				'uid_local' => $falCatUid,
				'uid_foreign' => $falMetadataUid,
				'tablenames' => 'sys_file_metadata',
				'fieldname' => 'categories',
				'damCatRefImported' => 1
			);
			
			// update sys_category_record_mm entry
			$GLOBALS['TYPO3_DB']->exec_INSERTquery (
				'sys_category_record_mm',
				$fieldsValuesCatRef,
				$no_quote_fields = FALSE
			);

			$falCatRefInfo = $falCatUid . ';' . $falMetadataUid . ';sys_file_metadata';

			$fieldsValuesForDamCatRefValues = array();
			$fieldsValuesForDamCatRefValues = array(
				'dammmcatalreadyexported' => '1',
				'falCatRefInfo' => $falCatRefInfo
			);

			// update dam entry
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery (
				'tx_dam_mm_cat',
				"uid_local = '" . $value['uid_local'] . "' AND uid_foreign = '" . $value['uid_foreign'] . "'",
				$fieldsValuesForDamCatRefValues,
				$no_quote_fields = FALSE
			);

		}

	}

	public function updateDAMCategoryTableWithFALId($damUid, $falUid) {

		$fieldsValuesForDamValues = array();
		$fieldsValuesForDamValues = array(
			'damcatalreadyexported' => '1',
			'falCatUid' => $falUid
		);

		// update dam entry
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery (
			'tx_dam_cat',
			"uid = '" . $damUid . "'",
			$fieldsValuesForDamValues,
			$no_quote_fields = FALSE
		);
	}

	public function updateSysFileReference($existingSysFileReferenceUid, $uidForeign, $tablename, $uidLocal, $ident) {

		// check if its tt_content, if it is filled with values take them and update
		if ($tablename == 'tt_content') {
			$row = $this->selectOneRowQuery('imagecaption, image_link, altText, titleText, sorting', $tablename, "uid='" . $uidForeign . "'", $groupBy = '', $orderBy = '', $limit = '');
			
			$titleText = $this->sortDescription($row['titleText'], $uidForeign, $txDamMmRefIdent, $tablenames, $uidLocal);
			$title = $titleText;
			if($title == ''){$title = NULL;}

			$imagecaption = $this->sortDescription($row['imagecaption'], $uidForeign, $txDamMmRefIdent, $tablenames, $uidLocal);
			$description = $imagecaption;
			if($description == ''){$description = NULL;}
			
			$alternativeText = $this->sortDescription($row['altText'], $uidForeign, $txDamMmRefIdent, $tablenames, $uidLocal);
			$alternative = $alternativeText;
			if($alternative == ''){$alternative = NULL;}
			
			$linkText = $this->sortDescription($row['image_link'], $uidForeign, $txDamMmRefIdent, $tablenames, $uidLocal);
			$link = $linkText;

			$sorting = $row['sorting'];
			
			$row = $this->selectOneRowQuery('sorting_foreign', 'tx_dam_mm_ref', "uid_foreign = '" . $uidForeign . "' AND uid_local = '" . $uidLocal . "' AND ident = '" . $txDamMmRefIdent . "' AND tablenames = '" . $txDamMmRefTablenames . "'", $groupBy = '', $orderBy = '', $limit = '');
			if ($row['sorting_foreign'] == NULL){$sorting_foreign = 0;}else{$sorting_foreign = $row['sorting_foreign'];}
			
			if ($sorting == NULL){
				$sorting = 0;
			}
			
			if ($sorting_foreign == NULL){
				$sorting_foreign = 0;
			}
			
			if ($link == NULL){
				$link = '';
			}

			$fieldsValuesForFALValues = array(
				'title' => $title,
				'description' => $description,
				'alternative' => $alternative,
				'link' => $link,
				'sorting' => $sorting,
				'sorting_foreign' => 0
			);

			// update sys_file_reference entry
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery (
				'sys_file_reference',
				"uid = '" . $existingSysFileReferenceUid . "'",
				$fieldsValuesForFALValues,
				$no_quote_fields = FALSE
			);

		}

		// update
		$this->updateDAMMMRefTableWithFALId($uidLocal, $uidForeign, $tablename, $ident, $existingSysFileReferenceUid);

	}
	
	public function sortDescription($imagecaption = '', $uidForeign = 0, $txDamMmRefIdent = '', $tablenames = '', $damUid = 0){
		if ($imagecaption == NULL){$imagecaption = '';}

		$imagecaptionArray = explode(PHP_EOL, $imagecaption);
		
		// read tx_dam_mm_ref order by sorting_foreign and take it for positioning, this position is then the right position for the caption array
		$rowMmRefSorting = $this->getArrayDataFromTable('*', 'tx_dam_mm_ref', "uid_foreign = '" . $uidForeign . "' AND ident = '" . $txDamMmRefIdent . "' AND tablenames = '" . $tablenames . "'", $groupBy = '', $orderBy = 'sorting_foreign ASC', $limit = '');
		
		$rowMmRefSortingCounter = 0;
		foreach ($rowMmRefSorting as $rowMmRefSortingKey => $rowMmRefSortingValue){
			if ($rowMmRefSortingValue['uid_foreign'] == $uidForeign && $rowMmRefSortingValue['uid_local'] == $damUid){
				$keyForCaption = $rowMmRefSortingCounter;
				break;
			}
			$rowMmRefSortingCounter++;
		}
		
		return $imagecaptionArray[$keyForCaption];
	
	}

	public function insertSysFileReference($falUid, $uidForeign, $tablenames, $fieldname, $sysLanguageUid, $damUid, $ident, $txDamMmRefTablenames, $txDamMmRefIdent) {

		// if sys_language_uid > 0 get parent entry from sys_file_reference through same uid_local
		if ($sysLanguageUid > 0) {
			$row = $this->selectOneRowQuery('uid', 'sys_file_reference', "uid_local='" . $falUid . "' and sys_language_uid = 0 and tablenames = '" . $tablenames . "'", $groupBy = '', $orderBy = '', $limit = '');
			$uidOfParent = $row['uid'];
		} else {
			$uidOfParent = 0;
		}

		// if tt_content, get information from tt_content		
		if ($tablenames == 'tt_content') {
			$row = $this->selectOneRowQuery('imagecaption, image_link, altText, titleText, sorting', $tablenames, "uid='" . $uidForeign . "'", $groupBy = '', $orderBy = '', $limit = '');

			$titleText = $this->sortDescription($row['titleText'], $uidForeign, $txDamMmRefIdent, $tablenames, $damUid);
			$title = $titleText;
			if($title == ''){$title = NULL;}

			$imagecaption = $this->sortDescription($row['imagecaption'], $uidForeign, $txDamMmRefIdent, $tablenames, $damUid);
			$description = $imagecaption;
			if($description == ''){$description = NULL;}
			
			$alternativeText = $this->sortDescription($row['altText'], $uidForeign, $txDamMmRefIdent, $tablenames, $damUid);
			$alternative = $alternativeText;
			if($alternative == ''){$alternative = NULL;}
			
			$linkText = $this->sortDescription($row['image_link'], $uidForeign, $txDamMmRefIdent, $tablenames, $damUid);
			$link = $linkText;

			$sorting = $row['sorting'];
			
			$row = $this->selectOneRowQuery('sorting_foreign', 'tx_dam_mm_ref', "uid_foreign = '" . $uidForeign . "' AND uid_local = '" . $damUid . "' AND ident = '" . $txDamMmRefIdent . "' AND tablenames = '" . $txDamMmRefTablenames . "'", $groupBy = '', $orderBy = '', $limit = '');
			if ($row['sorting_foreign'] == NULL){$sorting_foreign = 0;}else{$sorting_foreign = $row['sorting_foreign'];}
		} else {
			$row = $this->selectOneRowQuery('sorting, sorting_foreign', 'tx_dam_mm_ref', "uid_foreign = '" . $uidForeign . "' AND uid_local = '" . $damUid . "' AND ident = '" . $txDamMmRefIdent . "' AND tablenames = '" . $txDamMmRefTablenames . "'", $groupBy = '', $orderBy = '', $limit = '');
			$title = NULL;
			$description = NULL;
			$alternative = NULL;
			$link = '';
			$sorting = $row['sorting'];
			$sorting_foreign = $row['sorting_foreign'];
		}

		if ($sysLanguageUid == NULL){
			$sysLanguageUid = 0;
		}
		
		if ($sorting == NULL){
			$sorting = 0;
		}
		
		if ($sorting_foreign == NULL){
			$sorting_foreign = 0;
		}
		
		if ($link == NULL){
			$link = '';
		}
		
		$fieldsValuesForFALValues = array(
			'fieldname' => $fieldname,
			'deleted' => '0',
			'sorting' => $sorting,
			'sorting_foreign' => $sorting_foreign,
			'sys_language_uid' => $sysLanguageUid,
			't3_origuid' => '0',
			'uid_local' => $falUid,
			'uid_foreign' => $uidForeign,
			'tablenames' => $tablenames,
			'table_local' => 'sys_file',
			'title' => $title,
			'description' => $description,
			'alternative' => $alternative,
			'link' => $link,
			'l10n_parent' => $uidOfParent, // parent of sys_file_reference
			'hidden' => '0'
		);

		// if pages then update the pages table with media = 1
		if ($tablenames == 'pages'){
			
			$mediaValue = array(
				'media' => '1'
			);
		
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery (
				'pages',
				"uid = '" . $uidForeign . "'",
				$mediaValue,
				$no_quote_fields = FALSE
			);
		}
		
		$GLOBALS['TYPO3_DB']->exec_INSERTquery (
			'sys_file_reference',
			$fieldsValuesForFALValues,
			$no_quote_fields = FALSE
		);

		// get last inserted uid from sys_file table
		$lastInsertedIDForFAL = $GLOBALS['TYPO3_DB']->sql_insert_id();

		// update
		$this->updateDAMMMRefTableWithFALId($damUid, $uidForeign, $tablenames, $ident, $lastInsertedIDForFAL);
		
	}

	public function updateDAMMMRefTableWithFALId($uidLocal, $uidForeign, $tablenames, $ident, $falUid) {

		$fieldsValuesForDamValues = array();
		$fieldsValuesForDamValues = array(
			'dammmrefalreadyexported' => '1',
			'falUidRef' => $falUid
		);

		// update dam_mm_ref entry
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery (
			'tx_dam_mm_ref',
			"uid_local = '" . $uidLocal . "' and uid_foreign = '" . $uidForeign . "' and tablenames = '" . $tablenames . "' and ident = '" . $ident . "'",
			$fieldsValuesForDamValues,
			$no_quote_fields = FALSE
		);
	}

	public function updateDAMMMRefTableWithNoImportWanted($ident) {

		$fieldsValuesForDamValues = array();
		$fieldsValuesForDamValues = array(
			'dammmrefnoexportwanted' => '1'
		);

		// update dam_mm_ref entry
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery (
			'tx_dam_mm_ref',
			"ident = '" . $ident . "'",
			$fieldsValuesForDamValues,
			$no_quote_fields = FALSE
		);
	}

    public function getTablenamesForMultiselect() {
        $mmRefTablenames = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tablenames',
            'sys_file_reference',
            '', // where-clause
            $groupBy = 'tablenames', // group by
            $orderBy = '', // order by
            $limit = '' // limit
        );
        $extensionName = array();
        $counter=0;
        while($rowMmRefTablenames = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mmRefTablenames)) {
            $tablename = $rowMmRefTablenames['tablenames'];
            if ($tablename != 'tt_content' && $tablename != 'tt_news' && $tablename != '') {
                $extensionName[$tablename] = $tablename;
            }
            $counter++;
        }
        $extensionNameUnique = array_unique($extensionName);

        return $extensionNameUnique;
    }

	public function getExtensionNamesForMultiselect() {
		$mmRefTablenames = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tablenames',
            'tx_dam_mm_ref',
            'dammmrefalreadyexported != 1 AND dammmrefnoexportwanted != 1', // where-clause
            $groupBy = 'tablenames', // group by
            $orderBy = '', // order by
            $limit = '' // limit
        );
		$extensionName = array();
		$counter=0;
		while($rowMmRefTablenames = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mmRefTablenames)) {
			$tablename = $rowMmRefTablenames['tablenames'];
			if (substr($tablename,0,2) == 'tx') {
				$tablenameArray = array();
				$tablenameArray = explode('_', $tablename);
				$extensionName[$tablenameArray[1]] = $tablenameArray[1];
			} else {
				$extensionName[$tablename] = $tablename;
			}
			$counter++;
		}
		$extensionNameUnique = array_unique($extensionName);

		return $extensionNameUnique;
	}

	public function insertFalEntry($damUid) {

		$rowDamInfo = $this->selectOneRowQuery('*', 'tx_dam', "uid = '" . $damUid . "'");

		// check if there is fileadmin in the path and create identifier
		$filepath = $rowDamInfo['file_path'];
		$filename = $rowDamInfo['file_name'];

		if (substr(ltrim($filepath, '/'), 0, 9) == 'fileadmin') {
			$FALIdentifier = $this->getIdentifier($filepath, $filename);
			//$storage = 1;
			$storage = $this->getStorageForFile($filepath, $filename);
		} else {
			$FALIdentifier = $filepath.$filename;
			$storage = 0;
		}

		// check if there is a relation with l18n_parent
		if ($rowDamInfo['l18n_parent'] > 0) {
			// get filename and filepath from parent
			$rowDamInfoParent = $this->selectOneRowQuery('*', 'tx_dam', "uid = '" . $rowDamInfo['l18n_parent'] . "'");
			$FALIdentifier = $this->getIdentifier($rowDamInfoParent['file_path'], $rowDamInfoParent['file_name']);

			if (substr(ltrim($rowDamInfoParent['file_path'],'/'), 0, 9) == 'fileadmin') {
				// @TODO: implement Storage support (self::getStorageForFile)
				$storage = 1;
			} else {
				$storage = 0;
			}

			// get equivalent uid from sys_file entry
			$rowParentFALUid = $this->selectOneRowQuery('uid', 'sys_file', "identifier = '" . $FALIdentifier . "' and sys_language_uid = '0'", $groupBy = '', $orderBy = '', $limit = '');
			$parentFALUid = $rowParentFALUid['uid'];
		}else{
			$parentFALUid = 0;
		}
		
		// insert sys_file entry
		$fieldsValuesForFALValues = array(
			'storage' => $storage,
			'identifier' => $FALIdentifier,
			#'color_space' => $rowDamInfo['color_space'],
			#'deleted' => $rowDamInfo['deleted'],
			#'title' => $rowDamInfo['title'],
			#'keywords' => $rowDamInfo['keywords'],
			#'description' => $rowDamInfo['description'],
			#'alternative' => $rowDamInfo['alt_text'],
			#'caption' => $rowDamInfo['caption'],
			#'fe_groups' => $rowDamInfo['fe_group'],
			#'sys_language_uid' => $rowDamInfo['sys_language_uid'],
			#'t3_origuid' => $parentFALUid,
			#'l18n_parent' => $parentFALUid,
			#'location_country' => $rowDamInfo['loc_country'],
			#'creator' => $rowDamInfo['creator'],
			#'publisher' => $rowDamInfo['publisher'],
			#'status' => $rowDamInfo['file_status'],
			#'source' => $rowDamInfo['file_orig_location'],
			#'location_region' => '',
			#'location_city' => $rowDamInfo['loc_city'],
			#'latitude' => '',
			#'longitude' => '',
			#'categories' => $rowDamInfo['category'],
			#'unit' => $rowDamInfo['height_unit'],
			'damUid' => $damUid
		);

		$GLOBALS['TYPO3_DB']->exec_INSERTquery (
			'sys_file',
			$fieldsValuesForFALValues,
			$no_quote_fields=FALSE
		);
		
		// get last inserted uid from sys_file table
		$lastInsertedIDForFAL = $GLOBALS['TYPO3_DB']->sql_insert_id();
		
		// insert sys_file_metadata entry
		$fieldsValuesForFALMetadataValues = array(
			#'storage' => $storage,
			#'identifier' => $FALIdentifier,
			'color_space' => $rowDamInfo['color_space'],
			#'deleted' => $rowDamInfo['deleted'],
			'title' => $rowDamInfo['title'],
			'keywords' => $rowDamInfo['keywords'],
			'description' => $rowDamInfo['description'],
			'alternative' => $rowDamInfo['alt_text'],
			'caption' => $rowDamInfo['caption'],
			'fe_groups' => $rowDamInfo['fe_group'],
			'sys_language_uid' => $rowDamInfo['sys_language_uid'],
			't3_origuid' => '0',
			'l18n_parent' => '0',
			'file' => $lastInsertedIDForFAL,
			'location_country' => $rowDamInfo['loc_country'],
			'creator' => $rowDamInfo['creator'],
			'publisher' => $rowDamInfo['publisher'],
			'status' => $rowDamInfo['file_status'],
			'source' => $rowDamInfo['file_orig_location'],
			'download_name' => $rowDamInfo['file_dl_name'],
			'location_region' => '',
			'location_city' => $rowDamInfo['loc_city'],
			'latitude' => '',
			'longitude' => '',
			'categories' => $rowDamInfo['category'],
			'unit' => $rowDamInfo['height_unit'],
			'damUid' => $damUid
		);

		$GLOBALS['TYPO3_DB']->exec_INSERTquery (
			'sys_file_metadata',
			$fieldsValuesForFALMetadataValues,
			$no_quote_fields=FALSE
		);

		// update
		$this->updateDAMTableWithFALId($damUid, $lastInsertedIDForFAL);
	}

	public function selectOneRowQuery($fields, $table, $where, $groupBy = '', $orderBy = '', $limit = '') {

		$info = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            $fields,	// fields
            $table, // table
            $where, // where-clause
            $groupBy, // group by
            $orderBy, // order by
            $limit // limit
        );
		$rowInfo = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($info);

		return $rowInfo;
	}
	/*
	public function getIdentifier($filepath, $filename) {
		$filepathWithoutFileadmin = str_replace('fileadmin/', '', $filepath);
		$completeIdentifierForFAL = $filepathWithoutFileadmin . $filename;
		return '/' . ltrim($completeIdentifierForFAL, '/');
	}*/
	
	protected function getStorage($uid) {
		$uid = (int) $uid;
		$storages = $this->getStorages();
		return isset($storages[$uid]) ? $storages[$uid] : FALSE;
	}
	
	protected function getStorages() {
		if (!is_array($this->storages)) {
			
			$this->_storages = array();
			
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid, name, driver, configuration',
				'sys_file_storage',
				'deleted <> 1'
			);

			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){

				// read the config
				// sys_file_storage is not localisable,
				// so the flexform path is data->sDEF->lDEF->{confvalue}->vDEF
				$config = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($row['configuration']);

				$row['configuration'] = array();
				foreach ($config['data']['sDEF']['lDEF'] as $key => $value) {
					$row['configuration'][$key] = $value['vDEF'];
				}
				$this->_storages[(int) $row['uid']] = $row;
			}
			
			unset($config);
		}
		return $this->_storages;
	}

	public function getStorageForFile($filepath, $filename) {
		foreach($this->getStorages() as $storage) {
			$basePath = $storage['configuration']['basePath'];
			if (strpos($filepath, $basePath) === 0) {
				return (int) $storage['uid'];
			}
		}
		return FALSE;
	}
	
	public function getIdentifier($filepath, $filename, $onlyIfFileExists = FALSE) {
		$filepathOfStorage = '';
		$filepathWithoutStorage = $filepath;
		foreach($this->getStorages() as $storage) {
			$basePath = rtrim($storage['configuration']['basePath'], '/') . '/';
			if (strpos($filepath, $basePath) === 0) {
				$filepathOfStorage = $basePath;
				$filepathWithoutStorage = substr($filepath, strlen($basePath));
			}
		}
		$completeIdentifierForFAL = rtrim($filepathWithoutStorage, '/') . '/' . $filename;
		$completeIdentifierForFAL = '/' . ltrim($completeIdentifierForFAL, '/');
		if (
		$onlyIfFileExists
		&& !file_exists(PATH_site . $filepathOfStorage . $filepathWithoutStorage . $filename)
		){
			return FALSE;
		}
		return $completeIdentifierForFAL;
	}
	
	public function insertFALEntryMetadata($falUid, $damUid, $damUidParent){
	
		$damInfo = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*',
            'tx_dam',
            "uid = '" . $damUid . "'", // where-clause
            $groupBy = '', // group by
            $orderBy = '', // order by
            $limit = '' // limit
        );
		$rowDamInfo = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($damInfo);
		
		$damInfoParent = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*',
            'tx_dam',
            "uid = '" . $damUidParent . "'", // where-clause
            $groupBy = '', // group by
            $orderBy = '', // order by
            $limit = '' // limit
        );
		$rowDamInfoParent = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($damInfoParent);
		
		// get right parent uid from sys_file_metadata
		$sysfilemetadataInfo = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'uid',
            'sys_file_metadata',
            "file = '" . $falUid . "' AND sys_language_uid = '0'", // where-clause
            $groupBy = '', // group by
            $orderBy = '', // order by
            $limit = '' // limit
        );
		$rowSysFileMetadataInfo = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($sysfilemetadataInfo);
		
		// workaround because NULL can be given, has to be string
		if ($rowDamInfo['caption'] == NULL){
			$captionWorkaround = '';
		}else{
			$captionWorkaround = $rowDamInfo['caption'];
		}
		
		// array for sys_file_metadata entries
		$fieldsValuesForFALMetadataValues = array(
			'sys_language_uid' => $rowDamInfo['sys_language_uid'],
			'file' => $falUid,
			//'t3_origuid' => $rowSysFileMetadataInfo['uid'],
			//'l10n_parent' => $rowSysFileMetadataInfo['uid'],
			't3_origuid' => empty($rowSysFileMetadataInfo['uid']) ? 0 : $rowSysFileMetadataInfo['uid'],
			'l10n_parent' => empty($rowSysFileMetadataInfo['uid']) ? 0 : $rowSysFileMetadataInfo['uid'],
			'color_space' => $rowDamInfo['color_space'],
			#'deleted' => $rowDamInfo['deleted'], n.v.
			'title' => $rowDamInfo['title'],
			'keywords' => $rowDamInfo['keywords'],
			'description' => $rowDamInfo['description'],
			'alternative' => $rowDamInfo['alt_text'],
			'caption' => $captionWorkaround,
			'fe_groups' => $rowDamInfo['fe_group'],
			'location_country' => $rowDamInfo['loc_country'],
			'creator' => $rowDamInfoParent['creator'],
			'publisher' => $rowDamInfoParent['publisher'],
			'status' => $rowDamInfo['file_status'],
			'source' => $rowDamInfoParent['file_orig_location'],
			'download_name' => $rowDamInfoParent['file_dl_name'],
			'location_region' => '',
			'location_city' => $rowDamInfo['loc_city'],
			'latitude' => '',
			'longitude' => '',
			'unit' => $rowDamInfoParent['height_unit'],
			'damUid' => $damUid,
			'categories' => $rowDamInfo['category']
		);

		$GLOBALS['TYPO3_DB']->exec_INSERTquery (
			'sys_file_metadata',
			$fieldsValuesForFALMetadataValues,
			$no_quote_fields=FALSE
		);

		$this->updateDAMTableWithFALId($damUid, $falUid);

	}
	
	public function updateFALEntryWithParent($falUid, $damUid, $damUidParent) {

		$damInfo = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*',
            'tx_dam',
            "uid = '" . $damUid . "'", // where-clause
            $groupBy = '', // group by
            $orderBy = '', // order by
            $limit = '' // limit
        );
		$rowDamInfo = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($damInfo);

		$damInfoParent = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*',
            'tx_dam',
            "uid = '" . $damUidParent . "'", // where-clause
            $groupBy = '', // group by
            $orderBy = '', // order by
            $limit = '' // limit
        );
		$rowDamInfoParent = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($damInfoParent);
		
		// array for sys_file entries
		$fieldsValuesForFALValues = array(
			#'sys_language_uid' => $rowDamInfo['sys_language_uid'], n.v.
			#'color_space' => $rowDamInfo['color_space'], n.v.
			#'deleted' => $rowDamInfo['deleted'], n.v.
			#'title' => $rowDamInfo['title'], n.v.
			#'keywords' => $rowDamInfo['keywords'], n.v.
			#'description' => $rowDamInfo['description'], n.v.
			#'alternative' => $rowDamInfo['alt_text'], n.v.
			#'caption' => $rowDamInfo['caption'], n.v.
			#'fe_groups' => $rowDamInfo['fe_group'], n.v.			
			#'t3_origuid' => '', n.v.
			#'location_country' => $rowDamInfo['loc_country'],
			#'creator' => $rowDamInfoParent['creator'],
			#'publisher' => $rowDamInfoParent['publisher'],
			#'status' => $rowDamInfo['file_status'],
			#'source' => $rowDamInfoParent['file_orig_location'],
			#'location_region' => '',
			#'location_city' => $rowDamInfo['loc_city'],
			#'latitude' => '',
			#'longitude' => '',
			#'categories' => $rowDamInfo['category'],
			#'unit' => $rowDamInfoParent['height_unit'],
			'damUid' => $damUid
		);
		
		// update fal entry
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery (
			'sys_file',
			"uid = '" . $falUid . "'",
			$fieldsValuesForFALValues,
			$no_quote_fields = FALSE
		);
		
		// array for sys_file_metadata entries
		$fieldsValuesForFALMetadataValues = array(			
			#'sys_language_uid' => $rowDamInfo['sys_language_uid'],
			'color_space' => $rowDamInfo['color_space'],
			#'deleted' => $rowDamInfo['deleted'], n.v.
			'title' => $rowDamInfo['title'],
			'keywords' => $rowDamInfo['keywords'],
			'description' => $rowDamInfo['description'],
			'alternative' => $rowDamInfo['alt_text'],
			'caption' => $rowDamInfo['caption'],
			'fe_groups' => $rowDamInfo['fe_group'],
			'location_country' => $rowDamInfo['loc_country'],
			'creator' => $rowDamInfoParent['creator'],
			'publisher' => $rowDamInfoParent['publisher'],
			'status' => $rowDamInfo['file_status'],
			'source' => $rowDamInfoParent['file_orig_location'],
			'download_name' => $rowDamInfoParent['file_dl_name'],
			'location_region' => '',
			'location_city' => $rowDamInfo['loc_city'],
			'latitude' => '',
			'longitude' => '',
			'unit' => $rowDamInfoParent['height_unit'],
			'damUid' => $damUid,

			'categories' => $rowDamInfo['category']
			#'t3_origuid' => '',
	
		);

		// update fal metadata entry
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery (
			'sys_file_metadata',
			"file = '" . $falUid . "' AND sys_language_uid = '" . $rowDamInfo['sys_language_uid'] . "'",
			$fieldsValuesForFALMetadataValues,
			$no_quote_fields = FALSE
		);

		$this->updateDAMTableWithFALId($damUid, $falUid);

	}

	public function updateDAMTableWithFALId($damUid, $falUid) {

		$fieldsValuesForDamValues = array();
		$fieldsValuesForDamValues = array(
			'damalreadyexported' => '1',
			'falUid' => $falUid
		);

		// update dam entry
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery (
			'tx_dam',
			"uid = '" . $damUid . "'",
			$fieldsValuesForDamValues,
			$no_quote_fields = FALSE
		);
	}

	public function updateFALEntry($falUid, $damUid) {

		$damInfo = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*',
            'tx_dam',
            "uid = '" . $damUid . "'", // where-clause
            $groupBy = '', // group by
            $orderBy = '', // order by
            $limit = '' // limit
        );
		$rowDamInfo = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($damInfo);
		
		// update sys_file entry
		$fieldsValuesForFALValues = array(
			#'color_space' => $rowDamInfo['color_space'],
			#'deleted' => $rowDamInfo['deleted'],
			#'title' => $rowDamInfo['title'],
			#'keywords' => $rowDamInfo['keywords'],
			#'description' => $rowDamInfo['description'],
			#'alternative' => $rowDamInfo['alt_text'],
			#'caption' => $rowDamInfo['caption'],
			#'fe_groups' => $rowDamInfo['fe_group'],
			#'sys_language_uid' => $rowDamInfo['sys_language_uid'],
			#'t3_origuid' => '',
			#'location_country' => $rowDamInfo['loc_country'],
			#'creator' => $rowDamInfo['creator'],
			#'publisher' => $rowDamInfo['publisher'],
			#'status' => $rowDamInfo['file_status'],
			#'source' => $rowDamInfo['file_orig_location'],
			#'location_region' => '',
			#'location_city' => $rowDamInfo['loc_city'],
			#'latitude' => '',
			#'longitude' => '',
			#'categories' => $rowDamInfo['category'],
			#'unit' => $rowDamInfo['height_unit'],
			'damUid' => $damUid
		);

		// update fal entry
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery (
			'sys_file',
			"uid = '" . $falUid . "'",
			$fieldsValuesForFALValues,
			$no_quote_fields = FALSE
		);
		
		
		// update sys_file_metadata entry
		$fieldsValuesForFALValues = array(
			'color_space' => $rowDamInfo['color_space'],
			#'deleted' => $rowDamInfo['deleted'],
			'title' => $rowDamInfo['title'],
			'keywords' => $rowDamInfo['keywords'],
			'description' => $rowDamInfo['description'],
			'alternative' => $rowDamInfo['alt_text'],
			'caption' => $rowDamInfo['caption'],
			'fe_groups' => $rowDamInfo['fe_group'],
			#'sys_language_uid' => $rowDamInfo['sys_language_uid'],			
			'location_country' => $rowDamInfo['loc_country'],
			'creator' => $rowDamInfo['creator'],
			'publisher' => $rowDamInfo['publisher'],
			'status' => $rowDamInfo['file_status'],
			'source' => $rowDamInfo['file_orig_location'],
			'download_name' => $rowDamInfo['file_dl_name'],
			'location_region' => '',
			'location_city' => $rowDamInfo['loc_city'],
			'latitude' => '',
			'longitude' => '',
			'unit' => $rowDamInfo['height_unit'],
			
			'categories' => $rowDamInfo['category'],
			#'t3_origuid' => '',
			
			'damUid' => $damUid
		);
		
		// update fal entry
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery (
			'sys_file_metadata',
			"file = '" . $falUid . "' AND sys_language_uid = '" . $rowDamInfo['sys_language_uid'] . "'",
			$fieldsValuesForFALValues,
			$no_quote_fields = FALSE
		);
		

		$this->updateDAMTableWithFALId($damUid, $falUid);

	}

	public function getDamParentInformation($parentID) {

		$damParentInfoArray = array();

		$parentFileInfo = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'file_name, file_path',
            'tx_dam',
            "uid = '" . $parentID . "'", // where-clause
            $groupBy = '', // group by
            $orderBy = '', // order by
            $limit = '10000' // limit
        );

		$rowParentFileInfo = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($parentFileInfo);

        $damParentInfoArray['filename'] = $rowParentFileInfo['file_name'];
		$damParentInfoArray['filepath'] = $rowParentFileInfo['file_path'];

        return $damParentInfoArray;

	}

	public function getSpecificFALEntry($identifier, $name, $languageUid) {

		$specificFALEntry = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
		    'uid',
            'sys_file',
            "identifier = '" . $identifier . "' and name = '" . $name . "' and sys_language_uid = '" . $languageUid . "'", // where-clause
            $groupBy = '', // group by
            $orderBy = '', // order by
            $limit = '10000' // limit
        );
        return $specificFALEntry;

	}

    public function getCountedUidForeignsFromSysFileReference($fieldnameSysFileReference,$tablename, $fieldnameForeignTable) {

        $entries = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'uid_foreign',
            'sys_file_reference',
            "fieldname = '" . $fieldnameSysFileReference . "' AND tablenames = '" . $tablename . "' and deleted <> 1", // where-clause
            $groupBy = '', // group by
            $orderBy = '', // order by
            $limit = '' // limit
        );

        $arr = array();
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($entries)) {
           $arr[]  = $row['uid_foreign'];
        }
        $countedArr = array_count_values($arr);

        foreach ($countedArr as $countedkey => $countedvalue) {
            $fieldarray = array();
            $fieldarray = array(
                $fieldnameForeignTable  => $countedvalue
            );
            $this->updateTableEntry($tablename,"uid = '" . $countedkey . "'", $fieldarray);
        }

    }

	public function getArrayDataFromTable($fields, $tablename, $where, $groupBy = '', $orderBy = '', $limit = '10000') {

        $entries = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$fields,
            $tablename,
            $where, // where-clause
            $groupBy, // group by
            $orderBy, // order by
            $limit // limit
        );

		$arr = array();
		$counter = 1;
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($entries)) {

			if ($row['uid'] == 0 or $row['uid'] == '') {
				$number = $counter;
			} else {
				$number = $row['uid'];
			}

			$arr[$number] = $row;
			$counter++;
		}
        return $arr;
    }

    public function getTxDamEntriesNotImported() {

        $txDamEntries = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid, file_path, file_name, sys_language_uid, l18n_parent',
            'tx_dam',
            'damalreadyexported <> 1', // where-clause
            $groupBy = '', // group by
            $orderBy = '', // order by
            $limit = '10000' // limit
        );

        return $txDamEntries;

    }

	public function getProgressArray($table, $whereProgress, $whereOverall = '') {

        $getProgressArray = array();

		$columnFieldExists = $this->tableOrColumnFieldExist($table, 'field', $columnFieldname = 'uid_local');

		if ($columnFieldExists == TRUE) {
			$countVar = 'uid_local';
		}else{
			$countVar = 'uid';
		}
		
		if ($table == 'tx_dam_mm_cat'){
			$whereClauseForOverall = '';
		}else{
			$whereClauseForOverall = 'deleted = 0';
		}
		
        $getProgressArray['overall'] = $this->countTableEntries($countVar, $table, $whereClauseForOverall, $groupBy = '',$orderBy = '', $limit = '');

        $getProgressArray['actualcount'] = $this->countTableEntries($countVar, $table, $whereProgress, $groupBy = '', $orderBy = '', $limit = '');

        return $getProgressArray;
    }
	
}