#
# Table structure for table 'be_users'
#
CREATE TABLE be_users (
    tx_cdsrcbepwreset_resetAtNextLogin tinyint(4) DEFAULT '0' NOT NULL,
    tx_cdsrcbepwreset_resetHash varchar(60) DEFAULT '' NOT NULL,
    tx_cdsrcbepwreset_resetHashValidity int(11) DEFAULT '0' NOT NULL
);