/* NetFileMan SQLite 2 dump */ 

CREATE TABLE [users] (
[id] INTEGER  NOT NULL PRIMARY KEY,
[username] TEXT  NOT NULL,
[password] TEXT  NULL,
[role] TEXT  NOT NULL,
[real_name] TEXT  NULL,
[uploadroot] INTEGER  NULL,
[uploadpath] TEXT  NULL,
[lang] TEXT  NULL,
[quota_limit] INTEGER  NULL,
[quota] BOOLEAN  NULL,
[readonly] BOOLEAN  NULL,
[cache] BOOLEAN  NULL,
[imagemagick] BOOLEAN  NULL,
[has_share] BOOLEAN  NULL
);

INSERT INTO 'users' ('id','username','password','role','real_name','uploadroot','uploadpath','lang','quota_limit','quota','readonly','cache','imagemagick','has_share') VALUES
('1','root','63a9f0ea7bb98050796b649e85481845','admin','Root','','','en','','','','N','N','N');

CREATE TABLE [uploadroots] (
[id] INTEGER  NOT NULL PRIMARY KEY,
[path] TEXT  NULL
);