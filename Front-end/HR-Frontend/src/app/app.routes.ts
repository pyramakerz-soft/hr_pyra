import { Routes } from '@angular/router';
import { LoginComponent } from './Pages/login/login.component';
import { EmployeeDashboardComponent } from './Pages/employee-dashboard/employee-dashboard.component';
import { EmployeeComponent } from './Pages/Employee/employee/employee.component';
import { HREmployeeComponent } from './Pages/HR/hremployee/hremployee.component';
import { HRComponent } from './Pages/HR/hr/hr.component';
import { HrRoleComponent } from './Pages/HR/hr-role/hr-role.component';
import { HrRoleAddComponent } from './Pages/HR/hr-role-add/hr-role-add.component';
import { HrBoundersComponent } from './Pages/HR/hr-bounders/hr-bounders.component';
import { HrAttendanceComponent } from './Pages/HR/hr-attendance/hr-attendance.component';
import { HrEmployeeAttendanceDetailsComponent } from './Pages/HR/hr-employee-attendance-details/hr-employee-attendance-details.component';
import { HrEmployeeDetailsComponent } from './Pages/HR/hr-employee-details/hr-employee-details.component';
import { HrEmployeeAddEditDetailsComponent } from './Pages/HR/hr-employee-add-edit-details/hr-employee-add-edit-details.component';
import { AttendenceEditComponent } from './Pages/HR/attendence-edit/attendence-edit.component';
import { doNotNavigateWithoutLoginGuard } from './Guards/do-not-navigate-without-login.guard';
import { doNotNavigateToLoginIfTokenGuard } from './Guards/do-not-navigate-to-login-if-token.guard';
import { DashboardHeroComponent } from './Components/dashboard-hero/dashboard-hero.component';
import { HrDashboardComponent } from './Pages/HR/hr-dashboard/hr-dashboard.component';
import { DoughnutController } from 'chart.js';


export const routes: Routes = [
    {path: "employee", component:EmployeeComponent, title:"Dashboard", children:[
        {path: "", redirectTo: "Dashboard", pathMatch: "full" },
        {path: "Dashboard", component:EmployeeDashboardComponent, title:"Dashboard", canActivate:[doNotNavigateWithoutLoginGuard] },
    ], canActivate:[doNotNavigateWithoutLoginGuard]},

    {path: "HR", component:HRComponent, title:"HR", children:[
        {path: "", redirectTo: "HREmployee", pathMatch: "full"},
        {path: "HREmployee", component:HREmployeeComponent, title:"HREmployee", canActivate:[doNotNavigateWithoutLoginGuard]},
        {path: "HRDashBoard", component:HrDashboardComponent, title:"HRDashBoard", canActivate:[doNotNavigateWithoutLoginGuard]},
        {path: "HRRole", component:HrRoleComponent, title:"HRRole", canActivate:[doNotNavigateWithoutLoginGuard]},
        {path: "HRRoleAdd", component:HrRoleAddComponent, title:"HRRoleAdd", canActivate:[doNotNavigateWithoutLoginGuard]},
        {path: "HRBounders", component:HrBoundersComponent, title:"HRBounders", canActivate:[doNotNavigateWithoutLoginGuard]},
        {path: "HRAttendance", component:HrAttendanceComponent, title:"HRAttendance", canActivate:[doNotNavigateWithoutLoginGuard]},
        {path: "HREmployeeAttendanceDetails/:Id", component:HrEmployeeAttendanceDetailsComponent, title:"HREmployeeAttendanceDetails", canActivate:[doNotNavigateWithoutLoginGuard]},
        {path: "HREmployeeDetails/:EmpId", component:HrEmployeeDetailsComponent, title:"HREmployeeDetails", canActivate:[doNotNavigateWithoutLoginGuard]},
        {path: "HREmployeeDetailsAdd", component:HrEmployeeAddEditDetailsComponent, title:"HREmployeeDetailsAdd", canActivate:[doNotNavigateWithoutLoginGuard] },
        {path: "HREmployeeDetailsEdit/:Id", component:HrEmployeeAddEditDetailsComponent, title:"HREmployeeDetailsEdit", canActivate:[doNotNavigateWithoutLoginGuard]},
        {path: "HREmployeeAttendanceEdit", component:AttendenceEditComponent, title:"HREmployeeAttendanceEdit", canActivate:[doNotNavigateWithoutLoginGuard]},

    ], canActivate:[doNotNavigateWithoutLoginGuard]},

    { path: "dountChart", component:DoughnutController, title:"dountChart" },
    { path: "Login", component:LoginComponent, title:"Login", canActivate:[doNotNavigateToLoginIfTokenGuard] },
    { path: "", component:LoginComponent, title:"Login", canActivate:[doNotNavigateToLoginIfTokenGuard] },
    { path: '**', redirectTo: '/' },
];
