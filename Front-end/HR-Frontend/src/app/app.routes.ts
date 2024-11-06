import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
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
import { HrDepartmentComponent } from './Pages/HR/hr-department/hr-department.component';
import { HrDepartmentAddComponent } from './Pages/HR/hr-department-add/hr-department-add.component';
import { UserDataService } from './Services/Resolvers/user-data.service';
import { HrIssuesComponent } from './Pages/HR/hr-issues/hr-issues.component';

export const routes: Routes = [
    { 
        path: "employee", 
        component: EmployeeComponent, 
        title: "Dashboard", 
        canActivate: [doNotNavigateWithoutLoginGuard, navigateIfEmployeeGuard], 
        children: [
            { path: "", redirectTo: "Dashboard", pathMatch: "full" },
            { path: "Dashboard", component: EmployeeDashboardComponent, title: "Dashboard" }
        ]
    },

    { 
        path: "HR", 
        component: HRComponent, 
        title: "HR", 
        canActivate: [doNotNavigateWithoutLoginGuard, navigateIfHrGuard], 
        children: [
            { path: "", redirectTo: "HRDashboard", pathMatch: "full" },
            { path: "HRDashboard", component: HrDashboardComponent, title: "HRDashboard" },
            { path: "HREmployee", component: HREmployeeComponent, title: "HREmployee" },
            { path: "HRRole", component: HrRoleComponent, title: "HRRole" },
            { path: "HRRoleAdd", component: HrRoleAddComponent, title: "HRRoleAdd" },
            { path: "HRRoleEdit/:id", component: HrRoleAddComponent, title: "HRRoleEdit" },
            { path: "HRBounders", component: HrBoundersComponent, title: "HRBounders" },
            { path: "HRAttendance", component: HrAttendanceComponent, title: "HRAttendance" },
            { 
                path: "HRAttendanceEmployeeDetails/:Id", 
                component: HrEmployeeAttendanceDetailsComponent, 
                title: "HREmployeeAttendanceDetails", 
                resolve: { user: UserDataService }
            },
            { path: "HREmployeeDetails/:EmpId", component: HrEmployeeDetailsComponent, title: "HREmployeeDetails" },
            { path: "HREmployeeDetailsAdd", component: HrEmployeeAddEditDetailsComponent, title: "HREmployeeDetailsAdd" },
            { path: "HREmployeeDetailsEdit/:Id", component: HrEmployeeAddEditDetailsComponent, title: "HREmployeeDetailsEdit" },
            { path: "HRAttendanceEmployeeEdit/:Id", component: AttendenceEditComponent, title: "HRAttendanceEmployeeEdit" },
            { path: "HRDepartment", component: HrDepartmentComponent, title: "HRDepartment" },
            { path: "HRDepartmentAdd", component: HrDepartmentAddComponent, title: "HRDepartmentAdd" },
            { path: "HRDepartmentEdit/:id", component: HrDepartmentAddComponent, title: "HRDepartmentEdit" },
            { path: "HRIssues", component: HrIssuesComponent, title: "HRIssues" },

        ]
    },

    { path: "Login", component: LoginComponent, title: "Login", canActivate: [doNotNavigateToLoginIfTokenExistsGuard] },
    { path: "", redirectTo: "Login", pathMatch: "full" },
    { path: '**', redirectTo: 'Login' }
];

@NgModule({
    imports: [RouterModule.forRoot(routes, { useHash: true })],
    exports: [RouterModule]
})
export class AppRoutingModule { }
