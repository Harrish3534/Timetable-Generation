# DFD Diagram

## Level 0 DFD

```
+-------------+     +-------------+
|    Input    |     |  Process    |
|   Process   |---->|   System    |
+-------------+     +-------------+
     |    
     |    +-------------+ 
     |    |   Output    |
     +--->|   Process   |
          +-------------+ 
```

## Level 1 DFD

```
+-------------+      +-------------+     +-------------+
|   User     |      |   Process   |     |   Database  |
|             |----->|   Manage    |---->|             |
+-------------+      |             |     +-------------+
                      +-------------+            |
                             |                  |
                       +-------------+        |
                       |   Process   |<-------+
                       |   Retrieve   |
                       +-------------+
```

## Detailed Process Documentation
This document provides an overview of the Data Flow Diagrams (DFD) used in the Timetable Generation system. 
The purpose of these diagrams is to provide a visual representation of the flow of data through the system.

### Key Components:
1. **Processes**: Represented by circles or rectangles, they show the operations that transform data.
2. **Data Stores**: Represented by open-ended rectangles, they show where data is stored.
3. **External Entities**: Represented by squares, they are sources or destinations of data outside the system.