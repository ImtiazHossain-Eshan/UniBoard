-- Email: projectadmin@bracu.ac.bd
-- Password: admin123

INSERT INTO User (Student_ID, Name, GSuite_Email, Password, Created) 
VALUES (23101000, 'Project Administrator', 'projectadmin@bracu.ac.bd', '$2y$10$U.m7oN5uaHH4eDO35He3r.3M18BEPISSX9u9dmaCMal0aIXOAT90S', NOW());

INSERT INTO Role (St_ID, Role_name, Club_ID)
VALUES (23101000, 'Project_Admin', NULL);