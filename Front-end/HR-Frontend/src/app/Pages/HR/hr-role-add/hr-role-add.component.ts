import { Component, HostListener } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
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
  UserByIdPermissions:string[]=[]
  RoleName:string ="";
  isDropdownOpen = false;

  RoleNameError: string = ""; 
  namesArrayError: string = ""; 

  RoleId:number|undefined
  isSaving = false;

  constructor(private router: Router, private route: ActivatedRoute, public roleService:RolesService ,public PerService :PermissionsService) {}

  async ngOnInit() {
    await this.getAllpermissions();
    
    this.route.params.subscribe(async params => {
      if (params['id']) {
        this.RoleId = params['id'];
        await this.getRoleById();
        this.CheckTheSelectedPermissions();
      }
    });
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent) {
    const target = event.target as HTMLElement;
    const dropdown = document.querySelector('.dropdown-container') as HTMLElement;

    if (dropdown && !dropdown.contains(target)) {
      this.isDropdownOpen = false;
    }
  }

  // Cleanup event listener
  ngOnDestroy() {
    document.removeEventListener('click', this.onDocumentClick);
  }

  getRoleById(): Promise<void> {
    return new Promise((resolve, reject) => {
      if (this.RoleId) {
        this.roleService.getById(this.RoleId).subscribe(
          (result: any) => {
            this.RoleName = result.role[0].name;
            this.UserByIdPermissions = result.role[0].permissions;
            resolve();
          }
        );
      } else {
        resolve();
      }
    });
  }

  CheckTheSelectedPermissions(){
    this.UserByIdPermissions.forEach((role:any)=>{
      this.permissions.forEach((rolePermission:any)=>{
        if(role.name == rolePermission.name){
          rolePermission.selected = true
        }
      })
    })
  }

  getAllpermissions(): Promise<void> {
    return new Promise((resolve, reject) => {
      this.PerService.GetAll().subscribe(
        (d: any) => {
          this.permissions = d.permissions;
          resolve(); 
        }
      );
    });
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
    this.namesArray=  this.permissions
    .filter(p => p.selected).map(permission => permission.name);

    if(this.isFormValid()){
      this.isSaving = true;
      if(this.RoleId){
        this.roleService.updateRole(this.RoleName,this.namesArray, this.RoleId).subscribe(
          (d: any) => {
            this.router.navigateByUrl("/HR/HRRole");
          },
          (error) => {
            if (error.error.message === "The name has already been taken.") {
              Swal.fire({   
                text: "The Role name has already been taken",
                confirmButtonText: "OK",
                confirmButtonColor: "#FF7519",
              });
            }else{
              Swal.fire({   
                text: "Faild to create, Please Try again later",
                confirmButtonText: "OK",
                confirmButtonColor: "#FF7519",
                
              });
            }
          }
        );
      }else{
        this.roleService.createRole(this.RoleName,this.namesArray).subscribe(
          (d: any) => {
            this.router.navigateByUrl("/HR/HRRole");
          },
          (error) => {
            if (error.error.message === "The name has already been taken.") {
              Swal.fire({   
                text: "The Role name has already been taken",
                confirmButtonText: "OK",
                confirmButtonColor: "#FF7519",
              });
            }else{
              Swal.fire({   
                text: "Faild to create, Please Try again later",
                confirmButtonText: "OK",
                confirmButtonColor: "#FF7519",
                
              });
            }
          }
        );
      }
    }
  }


  cancel(){
    this.router.navigateByUrl("/HR/HRRole");

  }
}

