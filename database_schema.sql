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

CREATE TABLE Follows_club (
    Student_ID INT,
    Club_ID INT,
    PRIMARY KEY (Student_ID, Club_ID),
    FOREIGN KEY (Student_ID) REFERENCES User(Student_ID),
    FOREIGN KEY (Club_ID) REFERENCES Club(Club_ID)
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
    FOREIGN KEY (Student_ID) REFERENCES User(Student_ID),
    FOREIGN KEY (Membership_ID) REFERENCES ClubMembership(Membership_ID)
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
    FOREIGN KEY (Student_ID) REFERENCES User(Student_ID),
    FOREIGN KEY (Notification_ID) REFERENCES Notifications(Notification_ID)
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
    FOREIGN KEY (Student_ID) REFERENCES User(Student_ID),
    FOREIGN KEY (Rsvp_ID) REFERENCES RSVP(Rsvp_ID),
    FOREIGN KEY (Event_ID) REFERENCES Event(Event_ID)
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
    FOREIGN KEY (Event_ID) REFERENCES Event(Event_ID)
);