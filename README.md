This file named Tutorial2 contains the solutions of Tuto2(starting from the first tuto) & Tuto3 PAW and it also contains the  TEST Project named by attendance-system . This TEST Project respect the rules that the 
Final Assignment Details - Advanced Web Programming "Attached File" conatins , which are:

Project Requirements — Summary

This project consists of designing and building a complete **Web-Based Student Attendance Management System** including UI/UX design, frontend development, backend implementation, and database integration

1. Design Deliverables

Figma Prototype following a **mobile-first approach** ( Screenshots.png)

2. Frontend Deliverables

* Responsive, mobile-first user interface.
* Use of jQuery.
* Required pages:

**Professor Pages**

1. Home Page: List of sessions per course.
2. Session Page: Mark attendance for a specific session.
4. Attendance Summary: Table showing attendance per group/course.

 **Student Pages**

1. Home Page: List of enrolled courses.
2. Attendance Page: View attendance status and submit justification requests.

 **Administrator Pages**

1. Admin Home Page
2. Statistics Page (charts for attendance/participation)
3. Student List Management

   * Import/export student lists (Progres Excel format "in Import I used CSV format")
   * Add or remove students

**3. Backend Deliverables**

* Developed using PHP.

* Authentication system with user roles:

  * Student
  * Professor
  * Administrator

* Key backend functionalities:

  * Attendance session creation, opening, and closing
  * Attendance marking and updating
  * Justification submission + file storage
  * Participation and behavior tracking
  * Reporting logic for attendance and participation
  * Import/export of data (Excel)
  * Connection to **MariaDB/MySQL** with proper error handling:

    * try/catch
    * error logging

* CRUD operations required for:

  * Users (students, professors, admins)
  * Courses and groups
  * Attendance sessions
  * Attendance records
  * Justification requests (with file paths)

 **4. Technologies Used**

* Frontend: jQuery + responsive/mobile-first design
* Backend: PHP
* Database: MariaDB/MySQL ( I used Laragon App it contains MySQL )

* How to run the project: 

1.Import : setup-database.php , ( you can import any file of them " professor.php,..." )

2.Put project in Laragon www folder

3.Start Apache + MySQL

4.Open in a browser: localhost/attendance-system/setup-database.php  ( or any file.php)
