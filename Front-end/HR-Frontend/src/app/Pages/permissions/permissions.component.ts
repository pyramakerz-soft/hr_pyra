import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { PermissionsService } from '../../Services/permissions.service';
import { ActivatedRoute } from '@angular/router';
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
    public permissionService: PermissionsService
  ) {}

  ngOnInit(): void {
    this.permissionService.GetAll().subscribe({
      next: (data) => {
        this.permissions = data;
        console.log(this.permissions);
      },
      error: (err) => {
        console.error('Error fetching roles:', err);
      }
    });
  }
}
