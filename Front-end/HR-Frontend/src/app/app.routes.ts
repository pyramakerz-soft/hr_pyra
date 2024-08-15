import { Routes } from '@angular/router';
import { DashboardComponent } from './Pages/dashboard/dashboard.component';
import { UserComponent } from './Pages/user/user.component';
import { RolesComponent } from './Pages/roles/roles.component';
import { PermissionsComponent } from './Pages/permissions/permissions.component';
import { LoginComponent } from './Pages/login/login.component';
import { PermissionEditComponent } from './Pages/permission-edit/permission-edit.component';
import { SideBarComponent } from './Components/Core/side-bar/side-bar.component';
import { ClockInComponent } from './Components/clock-in/clock-in.component';
import { TableComponent } from './Components/Core/table/table.component';
import { EmployeeDashboardComponent } from './Pages/employee-dashboard/employee-dashboard.component';
import { EmployeeComponent } from './Pages/Employee/employee/employee.component';

export const routes: Routes = [
    // {path: "", component:DashboardComponent, title:"Dashboard", children:[
    //     {path: "", redirectTo: "Users", pathMatch: "full"},
    //     {path: "Users", component:UserComponent, title:"Users"},
    //     {path: "Roles", component:RolesComponent , title:"Roles"},
    //     {path: "Permissions", component:PermissionsComponent, title:"Permissions"},
    //     {path: "Permissions/Edit/:id", component:PermissionEditComponent, title:"PermissionsEdit"},
    // ]},

    {path: "employee", component:EmployeeComponent, title:"Dashboard", children:[
        {path: "", redirectTo: "Dashboard", pathMatch: "full"},
        {path: "Dashboard", component:EmployeeDashboardComponent, title:"Dashboard"},
    ]},


    { path: "Login", component:LoginComponent, title:"Login" },
    { path: '**', redirectTo: '/' },



];
