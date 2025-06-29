import { RoleModel } from "./role-model";

export class AddEmployee {
    constructor(
        public timezone_id: number | null,
        public image: File|null|string,
        public name: string,
        public code: string,
        public department_id: number | null,
        public sub_department_id: number | null,

        public deparment_name: string | null,
        public emp_type: string, // position
        public phone: string,
        public contact_phone: string,
        public email: string,
        public password: string,
        public national_id: string,
        public hiring_date: Date | null,
        public salary: number | null,
        public overtime_hours: number | null,
        public working_hours_day: number | null,
        public start_time: string | null,
        public end_time: string | null,
        public gender: string,
        public role: RoleModel | null = null,
        public location_id: number[],
        public location: string[],
        public work_type_id: number[],
        public work_type_name: string[],
        public work_home: boolean,
        // public is_float: number
    ) {}
}
