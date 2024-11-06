import { PermissionModel } from "./permission-model";

export class RoleModel {
    constructor(public id:number,public name:string , public Permissions : PermissionModel[]){}
}
