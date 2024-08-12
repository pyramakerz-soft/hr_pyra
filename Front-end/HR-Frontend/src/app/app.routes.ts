import { Routes } from '@angular/router';
import { DashboardComponent } from './Pages/dashboard/dashboard.component';
import { UserComponent } from './Pages/user/user.component';
import { RolesComponent } from './Pages/roles/roles.component';
import { PermissionsComponent } from './Pages/permissions/permissions.component';
import { LoginComponent } from './Pages/login/login.component';
import { PermissionEditComponent } from './Pages/permission-edit/permission-edit.component';
import { SideBarComponent } from './Components/Core/side-bar/side-bar.component';

export const routes: Routes = [
    {path: "", component:DashboardComponent, title:"Dashboard", children:[
        {path: "", redirectTo: "Users", pathMatch: "full"},
        {path: "Users", component:UserComponent, title:"Users"},
        {path: "Roles", component:RolesComponent , title:"Roles"},
        {path: "Permissions", component:PermissionsComponent, title:"Permissions"},
        {path: "Permissions/Edit/:id", component:PermissionEditComponent, title:"PermissionsEdit"},
        
    ]},
    { path: "SideBar", component:SideBarComponent, title:"SideBar" },
    { path: "Login", component:LoginComponent, title:"Login" },
    { path: "", component:DashboardComponent,title:"Dashboard" },
    { path: '**', redirectTo: '/' }
];
