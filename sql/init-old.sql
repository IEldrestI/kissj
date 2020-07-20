CREATE TABLE "user" (
	id     SERIAL PRIMARY KEY,
	email  TEXT NOT NULL,
	event  INT  NOT NULL,
	status TEXT NOT NULL
);

CREATE UNIQUE INDEX user_email_uindex
	ON "user" (email);

CREATE TABLE "logintoken" (
	id      SERIAL PRIMARY KEY,
	token   TEXT NOT NULL,
	userId  INT CONSTRAINT login_tokens_users_id_fk REFERENCES "user" (id),
	created TIMESTAMP,
	used    BOOLEAN
);

CREATE TABLE "patrolleader" (
	id                 SERIAL PRIMARY KEY,
	userId             INT CONSTRAINT patrolleader_userId_fk REFERENCES "user" (id),
	patrolName         TEXT,
	-- same as patrolparticipant
	firstName          TEXT,
	lastName           TEXT,
	nickname           TEXT,
	permanentResidence TEXT,
	telephoneNumber    TEXT,
	gender             TEXT,
	country            TEXT,
	email              TEXT,
	scoutUnit          TEXT,
	birthDate          TIMESTAMP,
	birthPlace         TEXT,
	allergies          TEXT,
	foodPreferences    TEXT,
	cardPassportNumber TEXT,
	tshirtSize         TEXT,
	scarf              TEXT,
	notes              TEXT
);

CREATE TABLE "patrolparticipant" (
	id                 SERIAL PRIMARY KEY,
	patrolleaderId     INT CONSTRAINT participant_patrolleaderId_fk REFERENCES "patrolleader" (id),
	firstName          TEXT,
	lastName           TEXT,
	nickname           TEXT,
	permanentResidence TEXT,
	telephoneNumber    TEXT,
	gender             TEXT,
	country            TEXT,
	email              TEXT,
	scoutUnit          TEXT,
	birthDate          TIMESTAMP,
	birthPlace         TEXT,
	allergies          TEXT,
	foodPreferences    TEXT,
	cardPassportNumber TEXT,
	tshirtSize         TEXT,
	scarf              TEXT,
	notes              TEXT
);

CREATE TABLE "ist" (
	id                   SERIAL PRIMARY KEY,
	userId               INT CONSTRAINT ist_userId_fk REFERENCES "user" (id),
	workPreferences      TEXT,
	skills               TEXT,
	languages            TEXT,
	arrivalDate          TIMESTAMP,
	leavingDate          TIMESTAMP,
	carRegistrationPlate TEXT,
	-- same as patrolparticipant
	firstName            TEXT,
	lastName             TEXT,
	nickname             TEXT,
	permanentResidence   TEXT,
	telephoneNumber      TEXT,
	gender               TEXT,
	country              TEXT,
	email                TEXT,
	scoutUnit            TEXT,
	birthDate            TIMESTAMP,
	birthPlace           TEXT,
	allergies            TEXT,
	foodPreferences      TEXT,
	cardPassportNumber   TEXT,
	tshirtSize           TEXT,
	scarf                TEXT,
	notes                TEXT
);

CREATE TABLE "guest" (
	id                   SERIAL PRIMARY KEY,
	userId               INT CONSTRAINT ist_userId_fk REFERENCES "user" (id),
	workPreferences      TEXT,
	skills               TEXT,
	languages            TEXT,
	arrivalDate          TIMESTAMP,
	leavingDate          TIMESTAMP,
	carRegistrationPlate TEXT,
	-- same as patrolparticipant
	firstName            TEXT,
	lastName             TEXT,
	nickname             TEXT,
	permanentResidence   TEXT,
	telephoneNumber      TEXT,
	gender               TEXT,
	country              TEXT,
	email                TEXT,
	scoutUnit            TEXT,
	birthDate            TIMESTAMP,
	birthPlace           TEXT,
	allergies            TEXT,
	foodPreferences      TEXT,
	cardPassportNumber   TEXT,
	tshirtSize           TEXT,
	scarf                TEXT,
	notes                TEXT
);

CREATE TABLE "payment" (
	id             SERIAL PRIMARY KEY,
	variableSymbol TEXT NOT NULL,
	price          TEXT NOT NULL,
	currency       TEXT NOT NULL,
	status         TEXT NOT NULL,
	purpose        TEXT NOT NULL,
	accountNumber  TEXT NOT NULL,
	generatedDate  TIMESTAMP NOT NULL,
	roleId         INT CONSTRAINT payment_roleId_fk REFERENCES "role" (id)
);

CREATE TABLE "event" (
	id                             SERIAL PRIMARY KEY,
	slug                           TEXT NOT NULL,
	readableName                   TEXT NOT NULL,
	accountNumber                  TEXT NOT NULL,
	prefixVariableSymbol           INT  NOT NULL,
	automaticPaymentPairing        INT  NOT NULL,
	bankId                         INT  NOT NULL,
	bankApi                        TEXT,
	allowPatrols                   INT  NOT NULL,
	maximalClosedPatrolsCount      INT  NOT NULL,
	minimalPatrolParticipantsCount INT  NOT NULL,
	maximalPatrolParticipantsCount INT  NOT NULL,
	allowIsts                      INT  NOT NULL,
	maximalClosedIstsCount         INT  NOT NULL
);
