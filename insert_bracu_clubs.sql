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