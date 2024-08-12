import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { PermissionsService } from '../../Services/permissions.service';
import { ActivatedRoute, Router } from '@angular/router';
import { RoleModel } from '../../Models/role-model';
import { PermissionModel } from '../../Models/permission-model';

@Component({
  selector: 'app-permissions',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './permissions.component.html',
  styleUrls: ['./permissions.component.css'] // Corrected property name
})
export class PermissionsComponent implements OnInit { // Implements OnInit

  permissions: PermissionModel[] = [];

  constructor(
    public ActivateRoute: ActivatedRoute,
    private router: Router,
    public permissionService: PermissionsService
  ) {}

  ngOnInit(): void {
    this.permissionService.GetAll().subscribe({
      next: (data:any) => {
        this.permissions = data.permissions;
        console.log(data.permissions);
      },
      error: (err) => {
        console.error('Error fetching roles:', err);
      }
    });
  }


  editPermission(id :number ,permission: PermissionModel): void {
    console.log('Permission updated ');
    this.router.navigate([`/Permissions/Edit/${id}`]); // Use backticks for template literals
  }



  delete(id: number): void {
    this.permissionService.Delete(id).subscribe({
      next: () => {
        this.permissions = this.permissions.filter((p) => p.id !== id);
        console.log('Permission deleted successfully');
      },
      error: (err) => {
        console.error('Error deleting permission:', err);
      }
    });
  }


}
