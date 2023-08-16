-- Adminer 4.8.1 PostgreSQL 13.11 dump

DROP TABLE IF EXISTS "alfa_snds";
DROP SEQUENCE IF EXISTS alfa_snds_id_seq;
CREATE SEQUENCE alfa_snds_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

CREATE TABLE "public"."alfa_snds" (
    "id" integer DEFAULT nextval('alfa_snds_id_seq') NOT NULL,
    "ip_address" character varying(15),
    "activity_period_start" text,
    "activity_period_end" text,
    "rcpt_commands" integer,
    "data_commands" integer,
    "message_recipients" integer,
    "filter_result" character varying(10),
    "complaint_rate" character varying(10),
    "trap_message_period_start" text,
    "trap_message_period_end" text,
    "trap_hits" integer,
    "sample_helo" character varying(255),
    "jmr_p1_sender" character varying(255),
    "comments" text,
    "hash" character varying(32),
    "node_id" integer,
    CONSTRAINT "alfa_snds_hash" UNIQUE ("hash"),
    CONSTRAINT "alfa_snds_pkey" PRIMARY KEY ("id")
) WITH (oids = false);


ALTER TABLE ONLY "public"."alfa_snds" ADD CONSTRAINT "alfa_snds_node_id_fkey" FOREIGN KEY (node_id) REFERENCES nodes(id) ON UPDATE CASCADE ON DELETE SET NULL NOT DEFERRABLE;

-- 2023-07-14 15:29:33.709575+02