#
# Add field to table 'tx_dam'
#
CREATE TABLE tx_dam (
	damalreadyexported int(1) unsigned DEFAULT '0' NOT NULL,
	falUid int(11) unsigned DEFAULT '0' NOT NULL,
);

#
# Add field to table 'sys_file'
#
CREATE TABLE sys_file (
	damUid int(11) unsigned DEFAULT '0' NOT NULL,
);

#
# Add field to table 'sys_file_metadata'
#
CREATE TABLE sys_file_metadata (
	damUid int(11) unsigned DEFAULT '0' NOT NULL,
);

#
# Add field to table 'tx_dam_mm_ref'
#
CREATE TABLE tx_dam_mm_ref (
	dammmrefalreadyexported int(1) unsigned DEFAULT '0' NOT NULL,
	dammmrefnoexportwanted int(1) unsigned DEFAULT '0' NOT NULL,
	falUidRef int(11) unsigned DEFAULT '0' NOT NULL,
);

#
# Add field to table 'sys_file_reference'
#
CREATE TABLE sys_file_reference (
	damUidRef int(11) unsigned DEFAULT '0' NOT NULL,
);

#
# Add field to table 'tx_dam_cat'
#
CREATE TABLE tx_dam_cat (
	damcatalreadyexported int(1) unsigned DEFAULT '0' NOT NULL,
	falCatUid int(11) unsigned DEFAULT '0' NOT NULL,
);

#
# Add field to table 'sys_category'
#
CREATE TABLE sys_category (
	damCatUid int(11) unsigned DEFAULT '0' NOT NULL,
);

#
# Add field to table 'tx_dam_mm_cat'
#
CREATE TABLE tx_dam_mm_cat (
	dammmcatalreadyexported int(1) unsigned DEFAULT '0' NOT NULL,
	falCatRefInfo varchar(255) DEFAULT '' NOT NULL,
);

#
# Add field to table 'sys_category_record_mm'
#
CREATE TABLE sys_category_record_mm (
	damCatRefImported int(11) unsigned DEFAULT '0' NOT NULL,
);
