import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { RoleModel } from '../../../Models/role-model';
import { RolesService } from '../../../Services/roles.service';

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
  }

  GetAllRoles():void{
    this.roleService.getall().subscribe(
      (d: any) => {
        this.tableData = d.roles; 
      },
      (error) => {
        console.error('Error retrieving user clocks:', error);
      }
    );
  }

  
  NavigateToAddRole(){
    this.router.navigateByUrl("/HR/HRRoleAdd");
  }


}
