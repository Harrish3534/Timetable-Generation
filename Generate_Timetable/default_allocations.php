<?php
/**
 * Default Manual Slot Allocations
<<<<<<< HEAD
 * 
 * Structure: [semester_type][class_index][shift][slot_key] = ['subject_id' => int, 'day' => string, 'hour' => string, 'staff_index' => null]
 *
 * semester_type: 'odd' or 'even'
 * class_index mapping:
 *   0 = I B.Sc (Sem 1 / Sem 2)
 *   1 = II B.Sc (Sem 3 / Sem 4)
 *   2 = III B.Sc (Sem 5 / Sem 6)
 *   3 = I M.Sc (Sem 1 / Sem 2)
 *   4 = II M.Sc (Sem 3 / Sem 4)
=======
 * * Structure: [semester_type][class_index][shift][slot_key] = ['subject_id' => int, 'day' => string, 'hour' => string, 'staff_index' => null]
 *
 * semester_type: 'odd' or 'even'
 * class_index mapping:
 * 0 = I B.Sc (Sem 1 / Sem 2)
 * 1 = II B.Sc (Sem 3 / Sem 4)
 * 2 = III B.Sc (Sem 5 / Sem 6)
 * 3 = I M.Sc (Sem 1 / Sem 2)
 * 4 = II M.Sc (Sem 3 / Sem 4)
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
 *
 * Days:  I DAY, II DAY, III DAY, IV DAY, V DAY, VI DAY
 * Hours: I HOUR, II HOUR, III HOUR, IV HOUR, V HOUR
 */

$default_manual_allocations = [

    'odd' => [
        // ──────────────────────────────────────────────────────────────
        // 0 = I B.Sc  (Semester 1 odd)
        // ──────────────────────────────────────────────────────────────
        0 => [

            // ── Shift 1 ──────────────────────────────────────────────
            'shift1' => [

                // Tamil I  (subject_id = 1, 5 hrs)
                'II DAY_IV HOUR' => ['subject_id' => 1, 'day' => 'II DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'III DAY_III HOUR' => ['subject_id' => 1, 'day' => 'III DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'IV DAY_IV HOUR' => ['subject_id' => 1, 'day' => 'IV DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'V DAY_III HOUR' => ['subject_id' => 1, 'day' => 'V DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'VI DAY_IV HOUR' => ['subject_id' => 1, 'day' => 'VI DAY', 'hour' => 'IV HOUR', 'staff_index' => null],

                // English I  (subject_id = 2, 4 hrs)
                'I DAY_IV HOUR' => ['subject_id' => 2, 'day' => 'I DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'II DAY_III HOUR' => ['subject_id' => 2, 'day' => 'II DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'III DAY_IV HOUR' => ['subject_id' => 2, 'day' => 'III DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'VI DAY_III HOUR' => ['subject_id' => 2, 'day' => 'VI DAY', 'hour' => 'III HOUR', 'staff_index' => null],

                // Statistics & Numerical Methods  (subject_id = 6, 5 hrs)
                'I DAY_V HOUR' => ['subject_id' => 6, 'day' => 'I DAY', 'hour' => 'V HOUR', 'staff_index' => null],
                'II DAY_I HOUR' => ['subject_id' => 6, 'day' => 'II DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'V DAY_II HOUR' => ['subject_id' => 6, 'day' => 'V DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'VI DAY_II HOUR' => ['subject_id' => 6, 'day' => 'VI DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'VI DAY_V HOUR' => ['subject_id' => 6, 'day' => 'VI DAY', 'hour' => 'V HOUR', 'staff_index' => null],

                // Environmental Studies (subject_id = 7, 2 hrs)
                'III DAY_V HOUR' => ['subject_id' => 7, 'day' => 'III DAY', 'hour' => 'V HOUR', 'staff_index' => null],
                'IV DAY_V HOUR' => ['subject_id' => 7, 'day' => 'IV DAY', 'hour' => 'V HOUR', 'staff_index' => null],
<<<<<<< HEAD
=======

                // Programming Methodology Lab  (subject_id = 5, 4 hrs)
                'I DAY_I HOUR' => ['subject_id' => 5, 'day' => 'I DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'I DAY_II HOUR' => ['subject_id' => 5, 'day' => 'I DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'V DAY_IV HOUR' => ['subject_id' => 5, 'day' => 'V DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'V DAY_V HOUR' => ['subject_id' => 5, 'day' => 'V DAY', 'hour' => 'V HOUR', 'staff_index' => null],
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
            ],

            // ── Shift 2 ──────────────────────────────────────────────
            'shift2' => [

                // Tamil I  (subject_id = 1, 5 hrs)
                'II DAY_I HOUR' => ['subject_id' => 1, 'day' => 'II DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'III DAY_II HOUR' => ['subject_id' => 1, 'day' => 'III DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'IV DAY_I HOUR' => ['subject_id' => 1, 'day' => 'IV DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'V DAY_III HOUR' => ['subject_id' => 1, 'day' => 'V DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'VI DAY_I HOUR' => ['subject_id' => 1, 'day' => 'VI DAY', 'hour' => 'I HOUR', 'staff_index' => null],

                // English I  (subject_id = 2, 4 hrs)
                'I DAY_I HOUR' => ['subject_id' => 2, 'day' => 'I DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'II DAY_II HOUR' => ['subject_id' => 2, 'day' => 'II DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'IV DAY_II HOUR' => ['subject_id' => 2, 'day' => 'IV DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'VI DAY_II HOUR' => ['subject_id' => 2, 'day' => 'VI DAY', 'hour' => 'II HOUR', 'staff_index' => null],

                // Statistics & Numerical Methods (subject_id = 6, 5 hrs)
                'I DAY_IV HOUR' => ['subject_id' => 6, 'day' => 'I DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'I DAY_V HOUR' => ['subject_id' => 6, 'day' => 'I DAY', 'hour' => 'V HOUR', 'staff_index' => null],
                'III DAY_IV HOUR' => ['subject_id' => 6, 'day' => 'III DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'IV DAY_V HOUR' => ['subject_id' => 6, 'day' => 'IV DAY', 'hour' => 'V HOUR', 'staff_index' => null],
                'V DAY_IV HOUR' => ['subject_id' => 6, 'day' => 'V DAY', 'hour' => 'IV HOUR', 'staff_index' => null],

                // Environmental Studies (subject_id = 7, 2 hrs)
                'V DAY_V HOUR' => ['subject_id' => 7, 'day' => 'V DAY', 'hour' => 'V HOUR', 'staff_index' => null],
                'VI DAY_IV HOUR' => ['subject_id' => 7, 'day' => 'VI DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
<<<<<<< HEAD
=======

                // Programming Methodology Lab  (subject_id = 5, 4 hrs)
                'II DAY_IV HOUR' => ['subject_id' => 5, 'day' => 'II DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'II DAY_V HOUR' => ['subject_id' => 5, 'day' => 'II DAY', 'hour' => 'V HOUR', 'staff_index' => null],
                'V DAY_I HOUR' => ['subject_id' => 5, 'day' => 'V DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'V DAY_II HOUR' => ['subject_id' => 5, 'day' => 'V DAY', 'hour' => 'II HOUR', 'staff_index' => null],
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
            ],
        ],

        // ──────────────────────────────────────────────────────────────
        // 1 = II B.Sc (Semester 3 odd)
        // ──────────────────────────────────────────────────────────────
        1 => [
            // ── Shift 1 ──────────────────────────────────────────────
            'shift1' => [
                // Tamil III (subject_id = 24, 4 hrs)
                'I DAY_I HOUR' => ['subject_id' => 24, 'day' => 'I DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'II DAY_II HOUR' => ['subject_id' => 24, 'day' => 'II DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'III DAY_I HOUR' => ['subject_id' => 24, 'day' => 'III DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'IV DAY_II HOUR' => ['subject_id' => 24, 'day' => 'IV DAY', 'hour' => 'II HOUR', 'staff_index' => null],

                // English III (subject_id = 25, 4 hrs)
                'I DAY_II HOUR' => ['subject_id' => 25, 'day' => 'I DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'II DAY_I HOUR' => ['subject_id' => 25, 'day' => 'II DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'III DAY_II HOUR' => ['subject_id' => 25, 'day' => 'III DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'IV DAY_I HOUR' => ['subject_id' => 25, 'day' => 'IV DAY', 'hour' => 'I HOUR', 'staff_index' => null],

                // Operations Research (subject_id = 30, 4 hrs)
                'II DAY_IV HOUR' => ['subject_id' => 30, 'day' => 'II DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'V DAY_IV HOUR' => ['subject_id' => 30, 'day' => 'V DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'V DAY_V HOUR' => ['subject_id' => 30, 'day' => 'V DAY', 'hour' => 'V HOUR', 'staff_index' => null],
                'VI DAY_I HOUR' => ['subject_id' => 30, 'day' => 'VI DAY', 'hour' => 'I HOUR', 'staff_index' => null],
<<<<<<< HEAD
=======

                // Java Programming Lab (subject_id = 29, 3 hrs)
                'V DAY_I HOUR' => ['subject_id' => 29, 'day' => 'V DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'V DAY_II HOUR' => ['subject_id' => 29, 'day' => 'V DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'V DAY_III HOUR' => ['subject_id' => 29, 'day' => 'V DAY', 'hour' => 'III HOUR', 'staff_index' => null],
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
            ],

            // ── Shift 2 ──────────────────────────────────────────────
            'shift2' => [
                // Tamil III (subject_id = 24, 4 hrs)
                'II DAY_III HOUR' => ['subject_id' => 24, 'day' => 'II DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'III DAY_IV HOUR' => ['subject_id' => 24, 'day' => 'III DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'V DAY_IV HOUR' => ['subject_id' => 24, 'day' => 'V DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'VI DAY_III HOUR' => ['subject_id' => 24, 'day' => 'VI DAY', 'hour' => 'III HOUR', 'staff_index' => null],

                // English III (subject_id = 25, 4 hrs)
                'II DAY_IV HOUR' => ['subject_id' => 25, 'day' => 'II DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'IV DAY_IV HOUR' => ['subject_id' => 25, 'day' => 'IV DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'V DAY_III HOUR' => ['subject_id' => 25, 'day' => 'V DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'VI DAY_IV HOUR' => ['subject_id' => 25, 'day' => 'VI DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
<<<<<<< HEAD
=======

                // Java Programming Lab (subject_id = 29, 3 hrs)
                'I DAY_III HOUR' => ['subject_id' => 29, 'day' => 'I DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'I DAY_IV HOUR' => ['subject_id' => 29, 'day' => 'I DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'I DAY_V HOUR' => ['subject_id' => 29, 'day' => 'I DAY', 'hour' => 'V HOUR', 'staff_index' => null],
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
            ],
        ],

        // ──────────────────────────────────────────────────────────────
        // 2 = III B.Sc (Semester 5 odd)
        // ──────────────────────────────────────────────────────────────
        2 => [
            // ── Shift 1 ──────────────────────────────────────────────
            'shift1' => [
                // Non Major Elective (subject_id = 46, 3 hrs)
                'I DAY_IV HOUR' => ['subject_id' => 46, 'day' => 'I DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'II DAY_IV HOUR' => ['subject_id' => 46, 'day' => 'II DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'III DAY_IV HOUR' => ['subject_id' => 46, 'day' => 'III DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
            ],

            // ── Shift 2 ──────────────────────────────────────────────
            'shift2' => [
                // Non Major Elective (subject_id = 46, 3 hrs)
                'I DAY_IV HOUR' => ['subject_id' => 46, 'day' => 'I DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'II DAY_IV HOUR' => ['subject_id' => 46, 'day' => 'II DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'III DAY_IV HOUR' => ['subject_id' => 46, 'day' => 'III DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
            ],
        ],
<<<<<<< HEAD

        // ──────────────────────────────────────────────────────────────
        // Add defaults for other class indices here for odd semesters
        // ──────────────────────────────────────────────────────────────
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
    ],

    'even' => [
        // ──────────────────────────────────────────────────────────────
        // 0 = I B.Sc  (Semester 2 even)
        // ──────────────────────────────────────────────────────────────
        0 => [
            // ── Shift 1 ──────────────────────────────────────────────
            'shift1' => [
                // Tamil II (subject_id = 16, 6 hrs)
                'I DAY_III HOUR' => ['subject_id' => 16, 'day' => 'I DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'II DAY_IV HOUR' => ['subject_id' => 16, 'day' => 'II DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'II DAY_V HOUR' => ['subject_id' => 16, 'day' => 'II DAY', 'hour' => 'V HOUR', 'staff_index' => null],
                'III DAY_III HOUR' => ['subject_id' => 16, 'day' => 'III DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'IV DAY_IV HOUR' => ['subject_id' => 16, 'day' => 'IV DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'VI DAY_IV HOUR' => ['subject_id' => 16, 'day' => 'VI DAY', 'hour' => 'IV HOUR', 'staff_index' => null],

                // English II (subject_id = 17, 5 hrs)
                'I DAY_IV HOUR' => ['subject_id' => 17, 'day' => 'I DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'II DAY_III HOUR' => ['subject_id' => 17, 'day' => 'II DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'III DAY_IV HOUR' => ['subject_id' => 17, 'day' => 'III DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'IV DAY_III HOUR' => ['subject_id' => 17, 'day' => 'IV DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'VI DAY_III HOUR' => ['subject_id' => 17, 'day' => 'VI DAY', 'hour' => 'III HOUR', 'staff_index' => null],

                // Discrete Mathematics (subject_id = 21, 6 hrs)
                'I DAY_V HOUR' => ['subject_id' => 21, 'day' => 'I DAY', 'hour' => 'V HOUR', 'staff_index' => null],
                'II DAY_I HOUR' => ['subject_id' => 21, 'day' => 'II DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'IV DAY_V HOUR' => ['subject_id' => 21, 'day' => 'IV DAY', 'hour' => 'V HOUR', 'staff_index' => null],
                'V DAY_II HOUR' => ['subject_id' => 21, 'day' => 'V DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'VI DAY_II HOUR' => ['subject_id' => 21, 'day' => 'VI DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'VI DAY_V HOUR' => ['subject_id' => 21, 'day' => 'VI DAY', 'hour' => 'V HOUR', 'staff_index' => null],

                // Value Education (subject_id = 23, 1 hr)
                'VI DAY_I HOUR' => ['subject_id' => 23, 'day' => 'VI DAY', 'hour' => 'I HOUR', 'staff_index' => null],
            ],

            // ── Shift 2 ──────────────────────────────────────────────
            'shift2' => [
                // Tamil II (subject_id = 16, 6 hrs)
                'I DAY_II HOUR' => ['subject_id' => 16, 'day' => 'I DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'II DAY_I HOUR' => ['subject_id' => 16, 'day' => 'II DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'III DAY_II HOUR' => ['subject_id' => 16, 'day' => 'III DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'IV DAY_I HOUR' => ['subject_id' => 16, 'day' => 'IV DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'V DAY_II HOUR' => ['subject_id' => 16, 'day' => 'V DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'VI DAY_I HOUR' => ['subject_id' => 16, 'day' => 'VI DAY', 'hour' => 'I HOUR', 'staff_index' => null],

                // English II (subject_id = 17, 5 hrs)
                'III DAY_I HOUR' => ['subject_id' => 17, 'day' => 'III DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'IV DAY_II HOUR' => ['subject_id' => 17, 'day' => 'IV DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'V DAY_I HOUR' => ['subject_id' => 17, 'day' => 'V DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'VI DAY_II HOUR' => ['subject_id' => 17, 'day' => 'VI DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'VI DAY_IV HOUR' => ['subject_id' => 17, 'day' => 'VI DAY', 'hour' => 'IV HOUR', 'staff_index' => null],

                // Discrete Mathematics (subject_id = 21, 6 hrs)
                'I DAY_IV HOUR' => ['subject_id' => 21, 'day' => 'I DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'I DAY_V HOUR' => ['subject_id' => 21, 'day' => 'I DAY', 'hour' => 'V HOUR', 'staff_index' => null],
                'II DAY_IV HOUR' => ['subject_id' => 21, 'day' => 'II DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'V DAY_III HOUR' => ['subject_id' => 21, 'day' => 'V DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'V DAY_IV HOUR' => ['subject_id' => 21, 'day' => 'V DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'VI DAY_V HOUR' => ['subject_id' => 21, 'day' => 'VI DAY', 'hour' => 'V HOUR', 'staff_index' => null],

                // Value Education (subject_id = 23, 1 hr)
                'VI DAY_III HOUR' => ['subject_id' => 23, 'day' => 'VI DAY', 'hour' => 'III HOUR', 'staff_index' => null],
            ],
        ],

        // ──────────────────────────────────────────────────────────────
        // 1 = II B.Sc (Semester 4 even)
        // ──────────────────────────────────────────────────────────────
        1 => [
            // ── Shift 1 ──────────────────────────────────────────────
            'shift1' => [
                // Tamil IV (subject_id = 32, 6 hrs)
                'I DAY_I HOUR' => ['subject_id' => 32, 'day' => 'I DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'II DAY_II HOUR' => ['subject_id' => 32, 'day' => 'II DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'III DAY_I HOUR' => ['subject_id' => 32, 'day' => 'III DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'IV DAY_II HOUR' => ['subject_id' => 32, 'day' => 'IV DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'V DAY_I HOUR' => ['subject_id' => 32, 'day' => 'V DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'VI DAY_II HOUR' => ['subject_id' => 32, 'day' => 'VI DAY', 'hour' => 'II HOUR', 'staff_index' => null],

                // English IV (subject_id = 33, 4 hrs)
                'I DAY_II HOUR' => ['subject_id' => 33, 'day' => 'I DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'II DAY_I HOUR' => ['subject_id' => 33, 'day' => 'II DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'IV DAY_I HOUR' => ['subject_id' => 33, 'day' => 'IV DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'VI DAY_I HOUR' => ['subject_id' => 33, 'day' => 'VI DAY', 'hour' => 'I HOUR', 'staff_index' => null],

                // Business Accounting (subject_id = 39, 4 hrs)
                'III DAY_II HOUR' => ['subject_id' => 39, 'day' => 'III DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'III DAY_III HOUR' => ['subject_id' => 39, 'day' => 'III DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'IV DAY_IV HOUR' => ['subject_id' => 39, 'day' => 'IV DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'IV DAY_V HOUR' => ['subject_id' => 39, 'day' => 'IV DAY', 'hour' => 'V HOUR', 'staff_index' => null],
            ],

            // ── Shift 2 ──────────────────────────────────────────────
            'shift2' => [
                // Tamil IV (subject_id = 32, 6 hrs)
                'I DAY_IV HOUR' => ['subject_id' => 32, 'day' => 'I DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'II DAY_III HOUR' => ['subject_id' => 32, 'day' => 'II DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'III DAY_IV HOUR' => ['subject_id' => 32, 'day' => 'III DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'IV DAY_III HOUR' => ['subject_id' => 32, 'day' => 'IV DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'V DAY_IV HOUR' => ['subject_id' => 32, 'day' => 'V DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'VI DAY_III HOUR' => ['subject_id' => 32, 'day' => 'VI DAY', 'hour' => 'III HOUR', 'staff_index' => null],

                // English IV (subject_id = 33, 4 hrs)
                'III DAY_III HOUR' => ['subject_id' => 33, 'day' => 'III DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'IV DAY_IV HOUR' => ['subject_id' => 33, 'day' => 'IV DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'V DAY_III HOUR' => ['subject_id' => 33, 'day' => 'V DAY', 'hour' => 'III HOUR', 'staff_index' => null],
                'VI DAY_IV HOUR' => ['subject_id' => 33, 'day' => 'VI DAY', 'hour' => 'IV HOUR', 'staff_index' => null],

                // Business Accounting (subject_id = 39, 4 hrs)
                'I DAY_I HOUR' => ['subject_id' => 39, 'day' => 'I DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'I DAY_II HOUR' => ['subject_id' => 39, 'day' => 'I DAY', 'hour' => 'II HOUR', 'staff_index' => null],
                'V DAY_I HOUR' => ['subject_id' => 39, 'day' => 'V DAY', 'hour' => 'I HOUR', 'staff_index' => null],
                'V DAY_II HOUR' => ['subject_id' => 39, 'day' => 'V DAY', 'hour' => 'II HOUR', 'staff_index' => null],
            ],
        ],

        // ──────────────────────────────────────────────────────────────
        // 2 = III B.Sc (Semester 6 even)
        // ──────────────────────────────────────────────────────────────
        2 => [
            // ── Shift 1 ──────────────────────────────────────────────
            'shift1' => [
                // Non Major Elective (subject_id = 56, 3 hrs)
                'I DAY_IV HOUR' => ['subject_id' => 56, 'day' => 'I DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'II DAY_IV HOUR' => ['subject_id' => 56, 'day' => 'II DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'III DAY_IV HOUR' => ['subject_id' => 56, 'day' => 'III DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
            ],

            // ── Shift 2 ──────────────────────────────────────────────
            'shift2' => [
                // Non Major Elective (subject_id = 56, 3 hrs)
                'I DAY_IV HOUR' => ['subject_id' => 56, 'day' => 'I DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'II DAY_IV HOUR' => ['subject_id' => 56, 'day' => 'II DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
                'III DAY_IV HOUR' => ['subject_id' => 56, 'day' => 'III DAY', 'hour' => 'IV HOUR', 'staff_index' => null],
            ],
        ]
    ]
<<<<<<< HEAD
];
=======
];
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
