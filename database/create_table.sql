-- for mysql

create table question (
    user_id int(11) not null,
    q1 varchar(1),
    q2 int,
    q3 int,
    q4 int,
    q5 int,
    q6 int,
    q7 int,
    q8 varchar(1),
    q9 int,
    q10 int,
    q11 int,
    q12 varchar(1),
    q13 varchar(10),
    q14 int,
    q15 int,
    round int not null default 1,
    ip varchar(46),
    create_date timestamp not null DEFAULT '0000-00-00 00:00:00',
    last_modified_date timestamp not null DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    primary key (user_id)
) default charset=utf8;



create table voter (
    user_id int(11) not null,
    user_name varchar(255),
    first_name varchar(255) not null,
    last_name varchar(255) ,
    chat_id int not null,
    member_type int not null default -100,
    authorized varchar(1) not null default 'N',
    lang varchar(2) not null default 'tc',
    stage varchar(50) not null default 'UNAUTHORIZED',
    voter_info int,
    age varchar(10),
    job int,
    ip varchar(46),
    create_date timestamp not null DEFAULT '0000-00-00 00:00:00',
    last_modified_date timestamp not null DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    primary key (user_id)
) default charset=utf8;

create table invitation (
    id int not null auto_increment,
    link varchar(20) not null,
    quota int not null,
    member_type int not null default -100,
    create_user_id int(11) not null,
    expire_date timestamp not null,
    create_date timestamp not null DEFAULT '0000-00-00 00:00:00',
    last_modified_date timestamp not null DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    primary key (id)
);

create table invitation_user(
    invitation_id int not null,
    user_id int not null,
    create_date timestamp not null DEFAULT '0000-00-00 00:00:00',
    last_modified_date timestamp not null DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    primary key (invitation_id, user_id)
);


create table audit_log(
    id int not null auto_increment,
    message text not null,
    create_date timestamp not null DEFAULT '0000-00-00 00:00:00',
    last_modified_date timestamp not null DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    primary key(id)
);


create table bulk (
    bulk_id int not null,
    chat_id int not null,
    lang varchar(2) not null default 'tc',
    status int(1) not null default 1, -- 0=Done, 1=Create, 2=Processing, 3=Error
    response varchar(500), -- Storing telegram response message
    last_modified_date timestamp not null default CURRENT_TIMESTAMP,
  primary key (bulk_id, chat_id)    
);

create table media_content (
    bulk_id int not null,
    lang varchar(2) not null default '*', -- * means all languages
    sequence int not null default 1, -- control sending sequence
    media_type int(1) not null default 1,
        -- 1=Text Message, 2=Reserved, 3=Photo, 4=Audio, 5=Document, 6=Sticker, 7=Video, 8=Voice, 9=Venue, 10=Contact, 11=ChatAction
        -- Please don't declare multiple type in same bulk_id
    media_content varchar(5000),
    media_status int not null default 1,
        -- 0=DISABLED, 1=INIT, 2=SAMPLE, 3=APPROVED
        --      INIT - Ready for sending sample for review
        --      SAMPLE - After sample sendout
        --      APPROVED - Available for bulk sending
        --      DISABLED - Bulk sending job will ignore the record, any status can change to DISABLED
        -- Media Lifecycle: INIT --> SAMPLE --> APPROVED
  PRIMARY KEY (`bulk_id`,`lang`, `sequence`)
);

create table system_content (
    content_id int(9) not null,
    content_version int not null default 0,
    content_type varchar(30) not null,
    content_name varchar(30) not null,
    content_value int(11) not null,
    content_tc varchar(800),
    content_en varchar(800),
  PRIMARY KEY (`content_id`)
);


