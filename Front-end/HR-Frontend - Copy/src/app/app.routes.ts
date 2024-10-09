import { Routes } from '@angular/router';
import { LoginComponent } from './Pages/login/login.component';
import { EmployeeDashboardComponent } from './Pages/employee-dashboard/employee-dashboard.component';
import { EmployeeComponent } from './Pages/Employee/employee/employee.component';
import { HrTableComponent } from './Components/Core/HR/hr-table/hr-table.component';
import { HREmployeeComponent } from './Pages/HR/hremployee/hremployee.component';
import { HRComponent } from './Pages/HR/hr/hr.component';
import { HrRoleComponent } from './Pages/HR/hr-role/hr-role.component';
import { HrRoleAddComponent } from './Pages/HR/hr-role-add/hr-role-add.component';
import { HrBoundersComponent } from './Pages/HR/hr-bounders/hr-bounders.component';
import { HrAttendanceComponent } from './Pages/HR/hr-attendance/hr-attendance.component';
import { HrEmployeeAttendanceDetailsComponent } from './Pages/HR/hr-employee-attendance-details/hr-employee-attendance-details.component';
import { HrEmployeeDetailsComponent } from './Pages/HR/hr-employee-details/hr-employee-details.component';
import { HrEmployeeAddEditDetailsComponent } from './Pages/HR/hr-employee-add-edit-details/hr-employee-add-edit-details.component';

export const routes: Routes = [
    {path: "employee", component:EmployeeComponent, title:"Dashboard", children:[
        {path: "", redirectTo: "Dashboard", pathMatch: "full"},
        {path: "Dashboard", component:EmployeeDashboardComponent, title:"Dashboard"},
    ]},

    {path: "HR", component:HRComponent, title:"HR", children:[
        {path: "", redirectTo: "HREmployee", pathMatch: "full"},
        {path: "HREmployee", component:HREmployeeComponent, title:"HREmployee"},
        {path: "HRRole", component:HrRoleComponent, title:"HRRole"},
        {path: "HRRoleAdd", component:HrRoleAddComponent, title:"HRRoleAdd"},
        {path: "HRBounders", component:HrBoundersComponent, title:"HRBounders"},
        {path: "HRAttendance", component:HrAttendanceComponent, title:"HRAttendance"},
        {path: "HREmployeeAttendanceDetails/:Id", component:HrEmployeeAttendanceDetailsComponent, title:"HREmployeeAttendanceDetails"},
        {path: "HREmployeeAttendanceDetails", component:HrEmployeeAttendanceDetailsComponent, title:"HREmployeeAttendanceDetails"},
        {path: "HREmployeeDetails/:EmpId", component:HrEmployeeDetailsComponent, title:"HREmployeeDetails"},
        {path: "HREmployeeDetailsAdd", component:HrEmployeeAddEditDetailsComponent, title:"HREmployeeDetailsAdd"},
        {path: "HREmployeeDetailsEdit/:Id", component:HrEmployeeAddEditDetailsComponent, title:"HREmployeeDetailsEdit"},
    ]},

    { path: "Login", component:LoginComponent, title:"Login" },
    { path: "HRtable", component:HREmployeeComponent, title:"HRtable" },
    { path: "HRtable", component:HrTableComponent, title:"HRtable" },
    { path: "", component:LoginComponent, title:"Login" },
    { path: '**', redirectTo: '/' },
];
