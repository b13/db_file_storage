CREATE TABLE tx_dbfilestorage_domain_model_file (
    uid int(11) unsigned NOT NULL auto_increment,
    pid int(11) unsigned DEFAULT '0' NOT NULL,
    tstamp int(11) unsigned DEFAULT '0' NOT NULL,
    crdate int(11) unsigned DEFAULT '0' NOT NULL,
    deleted tinyint(1) unsigned DEFAULT '0' NOT NULL,

    filename varchar(255) DEFAULT '' NOT NULL,
    mime_type varchar(127) DEFAULT '' NOT NULL,
    size bigint(20) unsigned DEFAULT '0' NOT NULL,
    sha1 varchar(40) DEFAULT '' NOT NULL,
    content longblob,

    PRIMARY KEY (uid),
    KEY sha1 (sha1)
);
