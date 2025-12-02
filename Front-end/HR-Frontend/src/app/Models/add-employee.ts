import { RoleModel } from "./role-model";

export class AddEmployee {
    constructor(
        public timezone_id: number | null = null,
        public image: File | null | string = null,
        public name: string = '',
        public code: string = '',
        public department_id: number | null = null,
        public sub_department_id: number | null = null,
        public deparment_name: string | null = null,
        public emp_type: string = '',
        public phone: string = '',
        public contact_phone: string = '',
        public email: string = '',
        public password: string = '',
        public national_id: string = '',
        public hiring_date: Date | null = null,
        public salary: number | null = null,
        public overtime_hours: number | null = null,
        public working_hours_day: number | null = null,
        public max_monthly_hours: number | null = null,
        public start_time: string | null = null,
        public end_time: string | null = null,
        public gender: string = '',
        public role: RoleModel | null = null,
        public location_id: number[] = [],
        public location: string[] = [],
        public work_type_id: number[] = [],
        public work_type_name: string[] = [],
        public work_home: boolean = false,
        public is_part_time: boolean = false,
        public works_on_saturday: boolean | null = null,
        public bank_name: string = '',
        public bank_account_number: string = '',
        // public is_float: number
    ) { }
}
