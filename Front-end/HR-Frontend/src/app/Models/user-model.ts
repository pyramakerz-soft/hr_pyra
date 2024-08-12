export class UserModel {
    constructor(
        public id: number,
        public name : string,
        public email: string,
        public phone: string,
        public contact_phone: string,
        public roleId: number[],
        public roleName: string[],
    ){}
}



// {
//     "image": null,
//     "department_id": 1,
//     "created_at": "2024-08-11T11:42:49.000000Z",
//     "updated_at": "2024-08-11T11:42:49.000000Z",
//     "user_detail": {
//         "id": 2,
//         "salary": "24000.00",
//         "hourly_rate": "100.00",
//         "working_hours_day": "8.00",
//         "overtime_hours": "1.50",
//         "emp_type": "Front developer",
//         "hiring_date": "2024-08-08",
//         "user_id": 1,
//         "created_at": "2024-08-11T11:42:49.000000Z",
//         "updated_at": "2024-08-11T11:42:49.000000Z"
//     },
//     "user_vacations": [
//         {
//             "id": 1,
//             "sick_left": 5,
//             "paid_left": 15,
//             "deduction_left": 1,
//             "user_id": 1,
//             "created_at": "2024-08-11T11:42:49.000000Z",
//             "updated_at": "2024-08-11T11:42:49.000000Z"
//         }
//     ],
//     "department": {
//         "id": 1,
//         "name": "Software",
//         "created_at": "2024-08-11T11:42:48.000000Z",
//         "updated_at": "2024-08-11T11:42:48.000000Z",
//         "manager_id": null,
//         "user_holidays": [
//             {
//                 "id": 2,
//                 "name": "25th revolution",
//                 "date_of_holiday": "2011-01-25",
//                 "department_id": 1,
//                 "user_id": null,
//                 "created_at": "2024-08-11T11:42:49.000000Z",
//                 "updated_at": "2024-08-11T11:42:49.000000Z"
//             }
//         ]
//     },
//     "roles": [
//         {
//             "id": 1,
//             "name": "Hr",
//             "guard_name": "api",
//             "created_at": "2024-08-11T11:42:49.000000Z",
//             "updated_at": "2024-08-11T11:42:49.000000Z",
//             "pivot": {
//                 "model_type": "App\\Models\\User",
//                 "model_id": 1,
//                 "role_id": 1
//             }
//         }
//     ]
// }