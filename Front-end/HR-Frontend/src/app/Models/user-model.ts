export class UserModel {
    constructor(
        public id: number,
        public name : string,
        public email: string,   
        public phone: string,
        public working_hours: string,
        public department: string,
        public role : string ,
        public position : string ,
        public code : string ,
        public clock_in_time? : string ,

        public selected?: boolean // Optional selected property

    ){}
}

