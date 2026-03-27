# DFD Complete Documentation

## Level 0 Context Diagram
```
+-------------------+
|     System        |
|                   |
|   Timetable       |
|   Generation      |
|                   |
+--------+----------+
         |  
         |  
         V  
+--------+----------+  
|   External        |  
|   Entities        |  
+-------------------+  

  1. Students       
  2. Teachers       
  3. Administrators 
  4. Courses        
  5. Resources      
```

## Level 1 Main Processes
```
1. Process Enrollment  (P1)
2. Schedule Generation (P2)
3. Resource Allocation (P3)
4. Conflict Resolution (P4)
5. Report Generation (P5)
```

## Level 2 Detailed Process Flows
```
P1: Process Enrollment
  +---------+        +-----------+
  |  Start  |------->|  Validate  |
  +---------+        +-----------+
                          |  
                          V  
                    +---------+
                    |  Save   |
                    |  Record |
                    +---------+
                          |
                          V 
                    +---------+ 
                    |  End   |  
                    +---------+
  
(P2): Schedule Generation
  +---------+        +-----------+
  |  Start  |------->| Generate   |
  +---------+        +-----------+
                          |  
                          V  
                    +---------+
                    | Review  |
                    +---------+
                          |
                          V 
                    +---------+ 
                    |  End   |  
                    +---------+
  
(P3): Resource Allocation
  +---------+        +-----------+
  |  Start  |------->| Allocate  |
  +---------+        +-----------+
                          |  
                          V  
                    +---------+
                    | Confirm  |
                    +---------+
                          |
                          V 
                    +---------+ 
                    |  End   |  
                    +---------+
  
(P4): Conflict Resolution
  +---------+        +-----------+
  |  Start  |------->| Identify  |
  +---------+        +-----------+
                          |  
                          V  
                    +---------+
                    | Resolve  |
                    +---------+
                          |
                          V 
                    +---------+ 
                    |  End   |  
                    +---------+
  
(P5): Report Generation
  +---------+        +-----------+
  |  Start  |------->| Generate  |
  +---------+        +-----------+
                          |  
                          V  
                    +---------+
                    | Review  |
                    +---------+
                          |
                          V 
                    +---------+ 
                    |  End   |  
                    +---------+
```

## Data Stores (D1 - D5)
### D1: Students
```sql
CREATE TABLE Students (
    StudentID INT PRIMARY KEY,
    Name VARCHAR(100),
    CourseID INT,
    EnrollmentDate DATE
);
```

### D2: Courses
```sql
CREATE TABLE Courses (
    CourseID INT PRIMARY KEY,
    CourseName VARCHAR(100),
    Credits INT
);
```

### D3: Teachers
```sql
CREATE TABLE Teachers (
    TeacherID INT PRIMARY KEY,
    Name VARCHAR(100)
);
```

### D4: Resources
```sql
CREATE TABLE Resources (
    ResourceID INT PRIMARY KEY,
    ResourceName VARCHAR(100)
);
```

### D5: Schedules
```sql
CREATE TABLE Schedules (
    ScheduleID INT PRIMARY KEY,
    CourseID INT,
    TeacherID INT,
    ResourceID INT,
    TimeSlot VARCHAR(50)
);
```

## Directory Structure Mapping
```
Timetable-Generation/
├── src/
│   ├── main/
│   │   └── java/
│   │       └── timetabling/
│   └── test/
└── lib/
```

## Constraints
- Foreign keys defined between tables (e.g., Courses with Students, Schedules with Courses).
- Unique constraints on certain fields (e.g., CourseID).

## Security Features
- User authentication mechanism for data access.
- Permission levels set for different user types (students, teachers, administrators).
- Data encryption for sensitive information (e.g., personal details).