import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { RoleModel } from '../../../Models/role-model';
import { RolesService } from '../../../Services/roles.service';
import Swal from 'sweetalert2';

interface data{
  role:string,
  desc:string,
}

@Component({
  selector: 'app-hr-role',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './hr-role.component.html',
  styleUrl: './hr-role.component.css'
})

export class HrRoleComponent {
  constructor(private router: Router , public roleService:RolesService) {}
  
  tableData:RoleModel[]= [];

  ngOnInit():void{
    this.GetAllRoles();
    localStorage.setItem('HrEmployeeCN', "1");
    localStorage.setItem('HrLocationsCN', "1");
    localStorage.setItem('HrAttendaceCN', "1");
    localStorage.setItem('HrAttanceDetailsCN', "1");


  }

  GetAllRoles():void{
    this.roleService.getall().subscribe(
      (d: any) => {
        this.tableData = d.roles; 
      }
    );
  }

  
  NavigateToAddRole(){
    this.router.navigateByUrl("/HR/HRRoleAdd");
  }

  deleteRole(id:number){
    Swal.fire({
      title: 'Are you sure you want to Delete This Role?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#FF7519',
      cancelButtonColor: '#17253E',
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        this.roleService.DeleteByID(id).subscribe(result => {
          this.GetAllRoles()
        });
      }
    });
  }

  navigateToEdit(id:number){
    this.router.navigateByUrl(`HR/HRRoleEdit/${id}`);
  }




}
