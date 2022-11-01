BEGIN TRANSACTION;
DROP TABLE IF EXISTS "mastodon_discussion";
CREATE TABLE IF NOT EXISTS "mastodon_discussion" (
	"images"	TEXT,
	"id"	INTEGER,
	"id_parent"	INTEGER,
	"profile_picture"	TEXT,
	"text"	TEXT,
	"author_screen_name"	TEXT,
	"author_handle"	TEXT,
	"date"	TEXT,
	"link_of_tweet"	TEXT,
	"videos"	TEXT,
	"timestamp"	text,
	"author_url"	TEXT,
	PRIMARY KEY("id","id_parent")
);
DROP TABLE IF EXISTS "mastodon_feed";
CREATE TABLE IF NOT EXISTS "mastodon_feed" (
	"protected"	NUMERIC,
	"profile_pictures"	TEXT,
	"images"	TEXT,
	"links"	TEXT,
	"id"	INTEGER,
	"text"	TEXT,
	"author_screen_name"	TEXT,
	"author_handle"	TEXT,
	"date"	TEXT,
	"link_of_tweet"	TEXT,
	"videos"	TEXT,
	"timestamp"	text,
	"author_url"	TEXT,
	PRIMARY KEY("id")
);
DROP TABLE IF EXISTS "citation";
CREATE TABLE IF NOT EXISTS "citation" (
	"images"	TEXT,
	"id"	INTEGER,
	"id_parent"	INTEGER,
	"profile_picture"	TEXT,
	"text"	TEXT,
	"author_screen_name"	TEXT,
	"author_handle"	TEXT,
	"date"	TEXT,
	"link_of_tweet"	TEXT,
	"videos"	TEXT,
	"timestamp"	text,
	PRIMARY KEY("id","id_parent")
);
DROP TABLE IF EXISTS "discussion";
CREATE TABLE IF NOT EXISTS "discussion" (
	"images"	TEXT,
	"id"	INTEGER,
	"id_parent"	INTEGER,
	"profile_picture"	TEXT,
	"text"	TEXT,
	"author_screen_name"	TEXT,
	"author_handle"	TEXT,
	"date"	TEXT,
	"link_of_tweet"	TEXT,
	"videos"	TEXT,
	"timestamp"	text,
	PRIMARY KEY("id","id_parent")
);
DROP TABLE IF EXISTS "feed";
CREATE TABLE IF NOT EXISTS "feed" (
	"protected"	NUMERIC,
	"profile_pictures"	TEXT,
	"images"	TEXT,
	"links"	TEXT,
	"id"	INTEGER,
	"text"	TEXT,
	"author_screen_name"	TEXT,
	"author_handle"	TEXT,
	"date"	TEXT,
	"link_of_tweet"	TEXT,
	"videos"	TEXT,
	"timestamp"	text,
	PRIMARY KEY("id")
);
DROP INDEX IF EXISTS "author_screen_name_index";
CREATE INDEX IF NOT EXISTS "author_screen_name_index" ON "feed" (
	"author_screen_name"	ASC
);
DROP INDEX IF EXISTS "citation_id_parent_index";
CREATE INDEX IF NOT EXISTS "citation_id_parent_index" ON "citation" (
	"id_parent"	ASC
);
DROP INDEX IF EXISTS "discussion_id_parent_index";
CREATE INDEX IF NOT EXISTS "discussion_id_parent_index" ON "discussion" (
	"id_parent"	ASC
);
DROP INDEX IF EXISTS "id_index";
CREATE UNIQUE INDEX IF NOT EXISTS "id_index" ON "feed" (
	"id"	ASC
);
COMMIT;
