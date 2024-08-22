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
  
  employee: AddEmployee = new AddEmployee(
    '', '', null, '', '', '', '', '', '', null, null, null, '', null, null, '', []
  );

  validationErrors: { [key in keyof AddEmployee]?: boolean | number | string | null | Date } = {};
  
  constructor(private route: ActivatedRoute,  
              public roleService: RolesService, 
              public departmentService: DepartmentService,
              public userService: UserServiceService
            ){}
  
  ngOnInit(): void {
    this.route.params.subscribe(params => {
      if (params['Id']) {
        this.EmployeeId = +params['Id'];
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

  isFormValid(): boolean {
    let isValid = true;

    for (const key in this.employee) {
      if (this.employee.hasOwnProperty(key)) {
        const field = key as keyof AddEmployee;
        if (!this.employee[field]) {
          this.validationErrors[field] = true;
          isValid = false;
        } else {
          this.validationErrors[field] = false;
        }
      }
    }

    if(this.employee.role.length == 0){
      this.validationErrors["role"] = true;
      isValid = false;
    } else {
      this.validationErrors["role"] = false;
    }

    return isValid;
  }

  onInputValueChange(event: { field: keyof AddEmployee, value: any }) {
    const { field, value } = event;
    if (field in this.employee) {
      (this.employee as any)[field] = value;
      if (value) {
        this.validationErrors[field] = false;
      }
    }
  }
  
  SaveEmployee() {
    if (this.isFormValid()) {
      this.employee.department_id = Number(this.employee.department_id);

      console.log(this.employee)

      if(this.EmployeeId === 0){
        this.userService.createUser(this.employee).subscribe(
          (result: any) => {
            console.log(result)
          },
          error => {
            console.error('Error:', error.error.errors);
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
}
