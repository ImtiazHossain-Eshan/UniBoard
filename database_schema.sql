CREATE DATABASE IF NOT EXISTS UniBoard; 
USE UniBoard;

CREATE TABLE User (
    Student_ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100),
    GSuite_Email VARCHAR(100) UNIQUE,
    RFID VARCHAR(50),
    Semester VARCHAR(20),
    Department VARCHAR(100),
    Address TEXT,
    Password VARCHAR(255),
    Gender VARCHAR(20),
    Profile_Pic VARCHAR(255),
    Created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Joined DATE,
    Phone_No VARCHAR(20),
    St_ID INT
);

CREATE TABLE Club (
    Club_ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100),
    Short_name VARCHAR(50),
    Description TEXT,
    Verified BOOLEAN DEFAULT FALSE,
    Verification_requested_at TIMESTAMP NULL,
    Created_by INT,
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Tag VARCHAR(50),
    FOREIGN KEY (Created_by) REFERENCES User(Student_ID) ON DELETE CASCADE
);

CREATE TABLE Role (
    Role_ID INT PRIMARY KEY AUTO_INCREMENT,
    St_ID INT NOT NULL,
    Role_name VARCHAR(50) NOT NULL,
    Club_ID INT DEFAULT NULL,
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_club_role (St_ID, Club_ID),
    FOREIGN KEY (St_ID) REFERENCES User(Student_ID) ON DELETE CASCADE,
    FOREIGN KEY (Club_ID) REFERENCES Club(Club_ID) ON DELETE CASCADE
);

CREATE TABLE Role_Request (
    Request_ID INT PRIMARY KEY AUTO_INCREMENT,
    Student_ID INT NOT NULL,
    Club_ID INT NOT NULL,
    Requested_Role ENUM('Club_President', 'Club_Admin') NOT NULL,
    Request_Message TEXT,
    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Reviewed_at TIMESTAMP NULL,
    Reviewed_by INT NULL,
    FOREIGN KEY (Student_ID) REFERENCES User(Student_ID) ON DELETE CASCADE,
    FOREIGN KEY (Club_ID) REFERENCES Club(Club_ID) ON DELETE CASCADE,
    FOREIGN KEY (Reviewed_by) REFERENCES User(Student_ID) ON DELETE SET NULL
);

CREATE TABLE Follows_club (
    Student_ID INT,
    Club_ID INT,
    PRIMARY KEY (Student_ID, Club_ID),
    FOREIGN KEY (Student_ID) REFERENCES User(Student_ID) ON DELETE CASCADE,
    FOREIGN KEY (Club_ID) REFERENCES Club(Club_ID) ON DELETE CASCADE
);

CREATE TABLE ClubMembership (
    Membership_ID INT PRIMARY KEY AUTO_INCREMENT,
    Report_date DATE,
    Role VARCHAR(50),
    Status VARCHAR(50),
    Joined_at TIMESTAMP
);

CREATE TABLE Joins_club (
    Student_ID INT,
    Membership_ID INT,
    PRIMARY KEY (Student_ID, Membership_ID),
    FOREIGN KEY (Student_ID) REFERENCES User(Student_ID) ON DELETE CASCADE,
    FOREIGN KEY (Membership_ID) REFERENCES ClubMembership(Membership_ID) ON DELETE CASCADE
);

CREATE TABLE Notifications (
    Notification_ID INT PRIMARY KEY AUTO_INCREMENT,
    Content TEXT,
    Type VARCHAR(50),
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Sent_at TIMESTAMP NULL,
    Is_read BOOLEAN DEFAULT FALSE
);

CREATE TABLE Gets_notification (
    Student_ID INT,
    Notification_ID INT,
    PRIMARY KEY (Student_ID, Notification_ID),
    FOREIGN KEY (Student_ID) REFERENCES User(Student_ID) ON DELETE CASCADE,
    FOREIGN KEY (Notification_ID) REFERENCES Notifications(Notification_ID) ON DELETE CASCADE
);

CREATE TABLE Location (
    Location_ID INT PRIMARY KEY AUTO_INCREMENT,
    Address TEXT,
    Building VARCHAR(100),
    Room VARCHAR(50),
    Capacity INT
);

CREATE TABLE EventType (
    Event_Type_ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(50),
    Status VARCHAR(50)
);

CREATE TABLE Event (
    Event_ID INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(150),
    Description TEXT,
    Start_time DATETIME,
    End_time DATETIME,
    Capacity INT,
    Is_public BOOLEAN,
    Updated_at TIMESTAMP,
    Tag VARCHAR(50),
    Location_ID INT,
    Club_ID INT,
    Event_Type_ID INT,
    FOREIGN KEY (Location_ID) REFERENCES Location(Location_ID),
    FOREIGN KEY (Club_ID) REFERENCES Club(Club_ID),
    FOREIGN KEY (Event_Type_ID) REFERENCES EventType(Event_Type_ID)
);

CREATE TABLE Notice (
    Notice_ID INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(200) NOT NULL,
    Content TEXT NOT NULL,
    Club_ID INT NOT NULL,
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Club_ID) REFERENCES Club(Club_ID) ON DELETE CASCADE
);

CREATE TABLE RSVP (
    Rsvp_ID INT PRIMARY KEY AUTO_INCREMENT,
    Created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Status VARCHAR(50)
);

CREATE TABLE Participate_in_events (
    Student_ID INT,
    Rsvp_ID INT,
    Event_ID INT,
    PRIMARY KEY (Student_ID, Event_ID),
    FOREIGN KEY (Student_ID) REFERENCES User(Student_ID) ON DELETE CASCADE,
    FOREIGN KEY (Rsvp_ID) REFERENCES RSVP(Rsvp_ID),
    FOREIGN KEY (Event_ID) REFERENCES Event(Event_ID) ON DELETE CASCADE
);

CREATE TABLE EventMedia (
    Media_ID INT PRIMARY KEY AUTO_INCREMENT,
    File_name VARCHAR(100),
    Media_url VARCHAR(255),
    Media_type VARCHAR(50),
    Uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Event_ID INT,
    FOREIGN KEY (Event_ID) REFERENCES Event(Event_ID)
);

CREATE TABLE EventAnalytics (
    Analytics_ID INT PRIMARY KEY AUTO_INCREMENT,
    Views INT DEFAULT 0,
    Interested_count INT DEFAULT 0,
    Going_count INT DEFAULT 0,
    Last_updated TIMESTAMP,
    Event_ID INT,
    FOREIGN KEY (Event_ID) REFERENCES Event(Event_ID) ON DELETE CASCADE
);

CREATE TABLE UserInterests (
    Student_ID INT,
    Event_Type_ID INT,
    PRIMARY KEY (Student_ID, Event_Type_ID),
    FOREIGN KEY (Student_ID) REFERENCES User(Student_ID) ON DELETE CASCADE,
    FOREIGN KEY (Event_Type_ID) REFERENCES EventType(Event_Type_ID) ON DELETE CASCADE
);


-- Insert default locations
INSERT INTO Location (Address, Building, Room, Capacity) VALUES
('MPH (Multipurpose Hall)', 'Main Campus', 'MPH', 500),
('BRACU Auditorium', 'Main Building', 'Auditorium', 300),
('Cricket Practice Field', 'Sports Complex', 'Outdoor', 100),
('Football Field', 'Sports Complex', 'Outdoor', 200),
('Theatre - 1', 'Academic Building', 'T1', 80),
('Theatre - 2', 'Academic Building', 'T2', 80),
('Theatre - 3', 'Academic Building', 'T3', 80),
('Theatre - 4', 'Academic Building', 'T4', 80),
('Theatre - 5', 'Academic Building', 'T5', 80),
('Theatre - 6', 'Academic Building', 'T6', 80),
('Theatre - 7', 'Academic Building', 'T7', 80),
('Theatre - 8', 'Academic Building', 'T8', 80),
('Theatre - 9', 'Academic Building', 'T9', 80),
('Theatre - 10', 'Academic Building', 'T10', 80);

-- Insert default event types
INSERT INTO EventType (Name, Status) VALUES
('Workshop', 'Active'),
('Seminar', 'Active'),
('Conference', 'Active'),
('Sports Event', 'Active'),
('Cultural Event', 'Active'),
('Competition', 'Active'),
('Social Gathering', 'Active'),
('Orientation', 'Active'),
('Training Session', 'Active'),
('Hackathon', 'Active'),
('Exhibition', 'Active'),
('Career Fair', 'Active');

-- NON-ACADEMIC CLUBS (Extra-Curricular)
INSERT INTO Club (Name, Short_name, Description, Verified, Tag, Created_at) VALUES
('Adventure Club', 'BUAC', 'Focuses on discovering and promoting natural beauty through various trips and activities.', TRUE, 'Non-Academic', NOW()),
('Art & Photography Society', 'BUAPS', 'Caters to students interested in visual arts and photography.', TRUE, 'Non-Academic', NOW()),
('Chess Club', 'BUCHC', 'Provides a platform for chess enthusiasts to play and compete.', TRUE, 'Non-Academic', NOW()),
('Communication & Language Club', 'BUCLC', 'Focuses on improving communication skills and celebrating languages.', TRUE, 'Non-Academic', NOW()),
('Community Service Club', 'BUCSC', 'Aims to use student capabilities to help the community through various initiatives.', TRUE, 'Non-Academic', NOW()),
('Cultural Club', 'BUCuC', 'Represents the universities traditional and cultural aspects through performances and events.', TRUE, 'Non-Academic', NOW()),
('Debating Club', 'BUDC', 'A forum for students to enhance their debating and public speaking skills.', TRUE, 'Non-Academic', NOW()),
('Drama and Theater Forum', 'BUDTF', 'Focuses on dramatic arts and theatrical performances.', TRUE, 'Non-Academic', NOW()),
('Entrepreneurship Forum', 'BUEDF', 'Encourages entrepreneurial spirit and idea development.', TRUE, 'Non-Academic', NOW()),
('Film Club', 'BUFC', 'For students interested in filmmaking and cinema.', TRUE, 'Non-Academic', NOW()),
('Leadership Development Forum', 'BULDF', 'Aims to develop leadership skills among students.', TRUE, 'Non-Academic', NOW()),
('MONON Club', 'MONON', 'A general club for student engagement.', TRUE, 'Non-Academic', NOW()),
('Multicultural Club', 'BUMC', 'Promotes cultural diversity and understanding within the university community.', TRUE, 'Non-Academic', NOW()),
('Peace Café BRAC University', 'PCBU', 'Focuses on discussions and activities related to peace and social harmony.', TRUE, 'Non-Academic', NOW()),
('Research for Development Club', 'BURed', 'Engages students in research activities for development purposes.', TRUE, 'Non-Academic', NOW()),
('Response Team', 'BURT', 'Involved in event management and safety/response services.', TRUE, 'Non-Academic', NOW()),
('Association of Business Communicators', 'IABC', 'Focuses on business communication skills.', TRUE, 'Non-Academic', NOW());

-- ACADEMIC CLUBS (Co-Curricular)
INSERT INTO Club (Name, Short_name, Description, Verified, Tag, Created_at) VALUES
('Business & Economics Forum', 'BUBeF', 'A forum for students interested in business and economic discussions.', TRUE, 'Academic', NOW()),
('Business Club', 'BIZBEE', 'Focuses specifically on business-related activities and knowledge sharing.', TRUE, 'Academic', NOW()),
('Computer Club', 'BUCC', 'Caters to students interested in computing, technology, and software development.', TRUE, 'Academic', NOW()),
('Economics Club', 'BUEC', 'For students pursuing or interested in economics.', TRUE, 'Academic', NOW()),
('Electrical & Electronic Club', 'BUEEC', 'A club for students in the Electrical and Electronic Engineering department.', TRUE, 'Academic', NOW()),
('Finance and Accounting Club', 'BUFIN', 'Focuses on finance and accounting principles and practices.', TRUE, 'Academic', NOW()),
('Law Society', 'BULC', 'For students of the School of Law.', TRUE, 'Academic', NOW()),
('Marketing Association', 'BUMA', 'Focuses on marketing principles and real-world application.', TRUE, 'Academic', NOW()),
('Natural Science Club', 'BUNSC', 'For students interested in natural sciences.', TRUE, 'Academic', NOW()),
('Pharmacy Society', 'BUPS', 'Caters to students in the School of Pharmacy.', TRUE, 'Academic', NOW()),
('Robotics Club', 'ROBU', 'Engages students in robotics design, building, and competitions.', TRUE, 'Academic', NOW());

-- SPORTS CLUBS
INSERT INTO Club (Name, Short_name, Description, Verified, Tag, Created_at) VALUES
('Cricket Club', 'CBU', 'Organizes cricket-related activities and teams.', TRUE, 'Sports', NOW()),
('Football Club', 'FCBU', 'Organizes football-related activities and teams.', TRUE, 'Sports', NOW()),
('Indoor Games Club', 'BUIGC', 'Promotes participation in various indoor sports and games.', TRUE, 'Sports', NOW()),
('E-sports Club', 'BUESC', 'Promotes and organizes participation in e-sports and games.', TRUE, 'Sports', NOW());

-- Total: 32 official BRAC University clubs
-- All marked as Verified=TRUE since they are official

-- Email: projectadmin@bracu.ac.bd
-- Password: admin123

INSERT INTO User (Student_ID, Name, GSuite_Email, Password, Created) 
VALUES (23101000, 'Project Administrator', 'projectadmin@bracu.ac.bd', '$2y$10$U.m7oN5uaHH4eDO35He3r.3M18BEPISSX9u9dmaCMal0aIXOAT90S', NOW());

INSERT INTO Role (St_ID, Role_name, Club_ID)
VALUES (23101000, 'Project_Admin', NULL);

-- Password: test123

INSERT INTO User (Student_ID, Name, GSuite_Email, Department, Password, Created) VALUES
(23101001, 'Rafiul Islam', 'rafiul.islam@g.bracu.ac.bd', 'CSE', '$2y$10$2qj8QdLn8QYskuMx4I1PZ.V7exXV7s2ha7A3436/efMAgFx3IvDC2', NOW()),
(23101002, 'Tasnia Rahman', 'tasnia.rahman@g.bracu.ac.bd', 'BBA', '$2y$10$2qj8QdLn8QYskuMx4I1PZ.V7exXV7s2ha7A3436/efMAgFx3IvDC2', NOW()),
(23101003, 'Fahim Ahmed', 'fahim.ahmed@g.bracu.ac.bd', 'EEE', '$2y$10$2qj8QdLn8QYskuMx4I1PZ.V7exXV7s2ha7A3436/efMAgFx3IvDC2', NOW()),
(23101004, 'Nusrat Jahan', 'nusrat.jahan@g.bracu.ac.bd', 'CSE', '$2y$10$2qj8QdLn8QYskuMx4I1PZ.V7exXV7s2ha7A3436/efMAgFx3IvDC2', NOW()),
(23101005, 'Tanvir Hasan', 'tanvir.hasan@g.bracu.ac.bd', 'BBA', '$2y$10$2qj8QdLn8QYskuMx4I1PZ.V7exXV7s2ha7A3436/efMAgFx3IvDC2', NOW()),
(23101006, 'Farzana Akter', 'farzana.akter@g.bracu.ac.bd', 'EEE', '$2y$10$2qj8QdLn8QYskuMx4I1PZ.V7exXV7s2ha7A3436/efMAgFx3IvDC2', NOW()),
(23101007, 'Sabbir Khan', 'sabbir.khan@g.bracu.ac.bd', 'CSE', '$2y$10$2qj8QdLn8QYskuMx4I1PZ.V7exXV7s2ha7A3436/efMAgFx3IvDC2', NOW()),
(23101008, 'Sadia Afrin', 'sadia.afrin@g.bracu.ac.bd', 'BBA', '$2y$10$2qj8QdLn8QYskuMx4I1PZ.V7exXV7s2ha7A3436/efMAgFx3IvDC2', NOW()),
(23101009, 'Mehedi Hasan', 'mehedi.hasan@g.bracu.ac.bd', 'Pharmacy', '$2y$10$2qj8QdLn8QYskuMx4I1PZ.V7exXV7s2ha7A3436/efMAgFx3IvDC2', NOW()),
(23101010, 'Lamia Sultana', 'lamia.sultana@g.bracu.ac.bd', 'Law', '$2y$10$2qj8QdLn8QYskuMx4I1PZ.V7exXV7s2ha7A3436/efMAgFx3IvDC2', NOW());