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
import { HrSubDepartmentComponent } from './Pages/HR/hr-sub-department/hr-sub-department.component';
import { HrSubDepartmentAddComponent } from './Pages/HR/hr-sub-department-add/hr-sub-department-add.component';
import { ShowTimezonesComponent } from './Pages/HR/timezone/show_timzones.component';
import { TimezoneAddEditComponent } from './Pages/HR/timezone/add_edit_timzones.component';
import { HrCustomVacationsComponent } from './Pages/HR/hr-custom-vacations/hr-custom-vacations.component';
import { HrServiceActionsComponent } from './Pages/HR/hr-service-actions/hr-service-actions.component';
import { HrNotificationsComponent } from './Pages/HR/hr-notifications/hr-notifications.component';

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

            { path: "HRSubDepartment/:id", component: HrSubDepartmentComponent, title: "HRSubDepartment" },
            { path: "HRSubDepartmentAdd/:deptId", component: HrSubDepartmentAddComponent, title: "HRSubDepartmentAdd" },
            { path: "HRSubDepartmentEdit/:deptId/:subDeptId", component: HrSubDepartmentAddComponent, title: "HRSubDepartmentEdit" },

            { path: "HRIssues", component: HrIssuesComponent, title: "HRIssues" },
            { path: "HRCustomVacations", component: HrCustomVacationsComponent, title: "HRCustomVacations" },
            { path: "HRServiceActions", component: HrServiceActionsComponent, title: "HRServiceActions" },
            { path: "HRNotifications", component: HrNotificationsComponent, title: "HRNotifications" },
            
            { path: "ShowTimezones", component: ShowTimezonesComponent, title: "ShowTimezones" },
            { path: "ShowTimezonesAdd", component: TimezoneAddEditComponent, title: "ShowTimezonesAdd" },
            { path: "ShowTimezonesEdit/:id", component: TimezoneAddEditComponent, title: "ShowTimezonesEdit" },


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
