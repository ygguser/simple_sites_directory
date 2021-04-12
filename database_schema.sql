CREATE TABLE IF NOT EXISTS "Categories" (
	"ID"	INTEGER,
	"Name"	TEXT,
	"Sorting"	INTEGER,
	PRIMARY KEY("ID")
);
CREATE TABLE IF NOT EXISTS "SitesCategories" (
	"Site"	INTEGER NOT NULL,
	"Category"	INTEGER NOT NULL,
	FOREIGN KEY("Site") REFERENCES "Sites"("ID") ON DELETE CASCADE,
	FOREIGN KEY("Category") REFERENCES "Categories"("ID") ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS "Sites" (
	"ID"	INTEGER,
	"URL"	TEXT NOT NULL,
	"Description"	TEXT,
	"Wyrd_DName"	TEXT,
	"ALFIS_DName"	TEXT,
	"Available"	INTEGER NOT NULL,
	"AvailabilityDate"	TEXT,
	"NumberOfChecks"	INTEGER,
	"NumberOfUnavailability"	INTEGER,
	PRIMARY KEY("ID")
);
