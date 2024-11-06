export class Department {
    constructor(
        public id: number,
        public name: string,
        public manager_id: number,
        public manager_name :string,
        public is_location_time : boolean
    ) {}
}
