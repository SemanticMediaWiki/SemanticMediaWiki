
-- Postgres schema for Semantic MediaWiki

\ON_ERROR_STOP
BEGIN;

CREATE TABLE smw_relations (
  subject_id         INTEGER  NOT NULL,
  subject_namespace  INTEGER  NOT NULL,
  subject_title      TEXT     NOT NULL,
  relation_title     TEXT     NOT NULL,
  object_id          INTEGER      NULL,
  object_namespace   INTEGER  NOT NULL,
  object_title       TEXT     NOT NULL
);
CREATE INDEX smw_relations_index1 ON smw_relations(subject_id, relation_title);
CREATE INDEX smw_relations_index2 ON smw_relations(object_id, object_title, object_namespace);

CREATE TABLE smw_attributes (
  subject_id         INTEGER  NOT NULL,
  subject_namespace  INTEGER  NOT NULL,
  subject_title      TEXT     NOT NULL,
  attribute_title    TEXT     NOT NULL,
  value_unit         TEXT     NOT NULL,
  value_datatype     TEXT     NOT NULL,
  value_xsd          TEXT     NOT NULL,
  value_num          FLOAT        NULL
);
CREATE INDEX smw_attributes_index1 ON smw_attributes(subject_id, attribute_title);
CREATE INDEX smw_attributes_index2 ON smw_attributes(value_num, value_xsd);

CREATE TABLE smw_longstrings (
  subject_id         INTEGER  NOT NULL,
  subject_namespace  INTEGER  NOT NULL,
  subject_title      TEXT     NOT NULL,
  attribute_title    TEXT     NOT NULL,
  value_blob         TEXT         NULL
);
CREATE INDEX smw_longstrings_index1 ON smw_longstrings(subject_id, attribute_title);

CREATE TABLE smw_nary (
  subject_id         INTEGER  NOT NULL,
  subject_namespace  INTEGER  NOT NULL,
  subject_title      TEXT     NOT NULL,
  attribute_title    TEXT     NOT NULL,
  nary_key           INTEGER  NOT NULL
);
CREATE INDEX smw_nary_index1 ON smw_nary(subject_id, attribute_title, nary_key);

CREATE TABLE smw_nary_relations (
  subject_id         INTEGER  NOT NULL,
  nary_key           INTEGER  NOT NULL,
  nary_pos           INTEGER  NOT NULL,
  object_id          INTEGER      NULL,
  object_namespace   INTEGER  NOT NULL,
  object_title       TEXT     NOT NULL
);
CREATE INDEX smw_nary_relations_index1 ON smw_nary_relations(subject_id,nary_key);
CREATE INDEX smw_nary_relations_index2 ON smw_nary_relations(object_id,object_namespace,object_title);

CREATE TABLE smw_nary_attributes (
  subject_id  INTEGER  NOT NULL,
  nary_key    INTEGER  NOT NULL,
  nary_pos    INTEGER  NOT NULL,
  value_unit  TEXT         NULL,
  value_xsd   TEXT     NOT NULL,
  value_num   FLOAT        NULL
);
CREATE INDEX smw_nary_attributes_index1 ON smw_nary_attributes(subject_id, nary_key);
CREATE INDEX smw_nary_attributes_index2 ON smw_nary_attributes(value_num, value_xsd);

CREATE TABLE smw_nary_longstrings (
  subject_id  INTEGER  NOT NULL,
  nary_key    INTEGER  NOT NULL,
  nary_pos    INTEGER  NOT NULL,
  value_blob  TEXT         NULL
);
CREATE INDEX smw_nary_longstrings_index1 ON smw_nary_longstrings(subject_id, nary_key);

CREATE TABLE smw_specialprops (
  subject_id         INTEGER NOT NULL,
  subject_namespace  INTEGER NOT NULL,
  property_id        SMALLINT NOT NULL,
  value_string       TEXT NOT NULL
);
CREATE INDEX smw_specialprops_index1 ON smw_specialprops(subject_id, property_id);

CREATE TABLE smw_subprops (
  subject_title  TEXT NOT NULL,
  object_title   TEXT NOT NULL
);
CREATE INDEX smw_subprops_index1 ON smw_subprops(subject_title, object_title);

COMMIT;

