import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { RolesService } from '../../../Services/roles.service';
import { PermissionModel } from '../../../Models/permission-model';
import { PermissionsService } from '../../../Services/permissions.service';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { PermissionAddModel } from '../../../Models/permission-add-model';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-hr-role-add',
  standalone: true,
  imports: [FormsModule,CommonModule],
  templateUrl: './hr-role-add.component.html',
  styleUrl: './hr-role-add.component.css'
})
export class HrRoleAddComponent {

  permissions:PermissionModel[]=[];
  Permissionarray:PermissionAddModel[]=[];
  namesArray:string[]=[]
  RoleName:string ="";
  isDropdownOpen = false;

  RoleNameError: string = ""; 
  namesArrayError: string = ""; 

  constructor(private router: Router , public roleService:RolesService ,public PerService :PermissionsService) {}

  ngOnInit(){
    this.getAllpermissions();
  }

  getAllpermissions():void{
    this.PerService.GetAll().subscribe(
      (d: any) => {
        this.permissions = d.permissions; 
      },
      (error) => {
        console.error('Error retrieving user clocks:', error);
      }
    );
  }

  toggleDropdown() {
    this.isDropdownOpen = !this.isDropdownOpen;
  }

  isFormValid(){
    let isValid = true
    this.RoleNameError = ""; 
    this.namesArrayError = "";  
    if (this.RoleName.trim() === "" && this.namesArray.length == 0) {
      isValid = false;
      this.RoleNameError = '*Role Name Can not be empty';
      this.namesArrayError = '*Choose a Permission';
    } else if (this.RoleName.trim() === "") {
      isValid = false;
      this.RoleNameError = '*Role Name Can not be empty';
    } else if (this.namesArray.length == 0) {
      isValid = false;
      this.namesArrayError = '*Choose a Permission';
    } 
    return isValid
  }

  onRoleChange() {
    this.RoleNameError = "" 
  }
  
  onArrayChange() {
    this.namesArrayError = "" 
  }

  save() {
  
  // this.Permissionarray = this.permissions
  // .filter(p => p.selected) // Step 1: Filter selected permissions
  // .map(p => new PermissionAddModel(p.name)); // Step 2: Map to PermissionAddModel
  
  // // Assuming Permissionarray is already populated with PermissionAddModel objects
  this.namesArray=  this.permissions
  .filter(p => p.selected).map(permission => permission.name);

  if(this.isFormValid()){
    this.roleService.createRole(this.RoleName,this.namesArray).subscribe(
      (d: any) => {
        this.router.navigateByUrl("/HR/HRRole");
      },
      (error) => {
        Swal.fire({   
          text: "Faild to create, Please Try again later",
          confirmButtonText: "OK",
          confirmButtonColor: "#FF7519",
          
        });
      }
    );
  }
  }
}
