import { Component } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { RolesService } from '../../../Services/roles.service';
import { RoleModel } from '../../../Models/role-model';
import { DepartmentService } from '../../../Services/department.service';
import { Department } from '../../../Models/department';
import { AddEmployee } from '../../../Models/add-employee';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { UserServiceService } from '../../../Services/user-service.service';

@Component({
  selector: 'app-hr-employee-add-edit-details',
  standalone: true,
  imports: [FormsModule, CommonModule],
  templateUrl: './hr-employee-add-edit-details.component.html',
  styleUrl: './hr-employee-add-edit-details.component.css'
})
export class HrEmployeeAddEditDetailsComponent {
  EmployeeId:number = 0
  roles: RoleModel[] = [];
  departments: Department[] = [];

  date = new Date();
  
  employee: AddEmployee = new AddEmployee(
    '', '', 0, '', '', '', '', '', '', new Date(), 0, 0, '', this.date, this.date, '', []
  );
  
  constructor(private route: ActivatedRoute,  
              public roleService: RolesService, 
              public departmentService: DepartmentService,
              public userService: UserServiceService
            ){}
  
  ngOnInit(): void {
    this.date.setHours(0, 0, 0, 0);

    this.route.params.subscribe(params => {
      if (params['EmpId']) {
        this.EmployeeId = +params['EmpId'];
        this.getEmployeeByID(this.EmployeeId)
      }
    });

    this.getDepartments()
    this.getRoles()
  }
  
  getEmployeeByID(id:number){
    this.userService.getUserById(id).subscribe(
      (d: any) => {
        this.employee = d.User;
        this.employee.role = this.employee.role || []
        console.log(this.employee)
      },
      (error) => {
        console.log(error)
      }
    );
  }

  getDepartments(){
    this.departmentService.getall().subscribe(
      (departments: any) => {
        this.departments = departments.data.departments
      },
      error => {
        console.error('Error fetching departments:', error);
      }
    );
  }

  getRoles(){
    this.roleService.getall().subscribe(
      (roles: any) => {
        this.roles = roles.roles
      },
      error => {
        console.error('Error fetching roles:', error);
      }
    );
  }

  onRoleChange(roleName: string, event: Event) {
    const isChecked = (event.target as HTMLInputElement).checked;

    if (isChecked) {
      if (!this.employee.role.includes(roleName)) {
        this.employee.role.push(roleName);
      }
    } else {
      const index = this.employee.role.indexOf(roleName);
      if (index > -1) {
        this.employee.role.splice(index, 1);
      }
    }
  }

  SaveEmployee() {
    this.employee.department_id = Number(this.employee.department_id);

    console.log(this.employee)
    console.log(this.EmployeeId)

    if(this.EmployeeId === 0){
      this.userService.createUser(this.employee).subscribe(
        (result: any) => {
          console.log(result)
        },
        error => {
          console.error('Error:', error);
        }
      );
    } else{
      this.userService.updateUser(this.employee, this.EmployeeId).subscribe(
        (result: any) => {
          console.log(result)
        },
        error => {
          console.error('Error:', error);
        }
      );
    }
  }
}
