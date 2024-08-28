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
import { navigateIfEmployeeGuard } from './Guards/navigate-if-employee.guard';
import { HrDashboardComponent } from './Pages/HR/hr-dashboard/hr-dashboard.component';
import { navigateIfHrGuard } from './Guards/navigate-if-hr.guard';
import { doNotNavigateToLoginIfTokenExistsGuard } from './Guards/do-not-navigate-to-login-if-token-exists.guard';

export const routes: Routes = [
    {path: "employee", component:EmployeeComponent, title:"Dashboard", children:[
        {path: "", redirectTo: "Dashboard", pathMatch: "full" },
        {path: "Dashboard", component:EmployeeDashboardComponent, title:"Dashboard", canActivate:[doNotNavigateWithoutLoginGuard, navigateIfEmployeeGuard] },
    ], canActivate:[doNotNavigateWithoutLoginGuard, navigateIfEmployeeGuard]},

    {path: "HR", component:HRComponent, title:"HR", children:[
        {path: "", redirectTo: "HREmployee", pathMatch: "full"},
        {path: "HREmployee", component:HREmployeeComponent, title:"HREmployee", canActivate:[doNotNavigateWithoutLoginGuard, navigateIfHrGuard]},
        {path: "HRDashBoard", component:HrDashboardComponent, title:"HRDashBoard", canActivate:[doNotNavigateWithoutLoginGuard, navigateIfHrGuard]},
        {path: "HRRole", component:HrRoleComponent, title:"HRRole", canActivate:[doNotNavigateWithoutLoginGuard, navigateIfHrGuard]},
        {path: "HRRoleAdd", component:HrRoleAddComponent, title:"HRRoleAdd", canActivate:[doNotNavigateWithoutLoginGuard, navigateIfHrGuard]},
        {path: "HRBounders", component:HrBoundersComponent, title:"HRBounders", canActivate:[doNotNavigateWithoutLoginGuard, navigateIfHrGuard]},
        {path: "HRAttendance", component:HrAttendanceComponent, title:"HRAttendance", canActivate:[doNotNavigateWithoutLoginGuard, navigateIfHrGuard]},
        {path: "HREmployeeAttendanceDetails/:Id", component:HrEmployeeAttendanceDetailsComponent, title:"HREmployeeAttendanceDetails", canActivate:[doNotNavigateWithoutLoginGuard, navigateIfHrGuard]},
        {path: "HREmployeeDetails/:EmpId", component:HrEmployeeDetailsComponent, title:"HREmployeeDetails", canActivate:[doNotNavigateWithoutLoginGuard, navigateIfHrGuard]},
        {path: "HREmployeeDetailsAdd", component:HrEmployeeAddEditDetailsComponent, title:"HREmployeeDetailsAdd", canActivate:[doNotNavigateWithoutLoginGuard, navigateIfHrGuard] },
        {path: "HREmployeeDetailsEdit/:Id", component:HrEmployeeAddEditDetailsComponent, title:"HREmployeeDetailsEdit", canActivate:[doNotNavigateWithoutLoginGuard, navigateIfHrGuard]},
        {path: "HREmployeeAttendanceEdit", component:AttendenceEditComponent, title:"HREmployeeAttendanceEdit", canActivate:[doNotNavigateWithoutLoginGuard, navigateIfHrGuard]},
    ], canActivate:[doNotNavigateWithoutLoginGuard, navigateIfHrGuard]},

    { path: "Login", component:LoginComponent, title:"Login", canActivate:[doNotNavigateToLoginIfTokenExistsGuard] },
    { path: "", component:LoginComponent, title:"Login", canActivate:[doNotNavigateToLoginIfTokenExistsGuard] },
    { path: '**', redirectTo: '/' },
];
