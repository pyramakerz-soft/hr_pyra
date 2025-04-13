export class SubDepartment {
    constructor(
        public id: number,
        public name: string,
        public teamlead_id: number,
        public department_id: number,
        public teamLeadName?: string // optional (nullable) field
    ) {}

    static fromJson(json: any): SubDepartment {
        return new SubDepartment(
            json.id,
            json.name,
            json.teamlead_id,
            json.department_id,
            json.team_lead?.name ?? null // use optional chaining and fallback to null
        );
    }
}
