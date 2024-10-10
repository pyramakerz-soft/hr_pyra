export class AddEmployee {
    constructor(
        // public photo: File|null
        public name: string,
        public code: string,
        public department_id: number,
        public emp_type: string, // position
        public phone: string,
        public contact_phone: string,
        public email: string,
        public password: string,
        public national_id: string,
        public hiring_date: Date,
        public salary: number,
        public overtime_hours: number,
        public working_hours_day: string,
        public start_time: Date,
        public end_time: Date,
        public gender: string,
        public role: string[]
    ) {}
}
