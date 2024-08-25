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
    '', '', null, '', '', '', '', '', '', null, null, null, null, null, null, '', []
  );

  regexPhone = /^(010|011|012|015)\d{8}$/;
  regexEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  regexNationalID = /^\d{14}$/;

  validationErrors: { [key in keyof AddEmployee]?: boolean | string } = {};
  
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

          switch (field){
            case "name":
              if(this.employee.name.length < 3){
                console.log("Name Must be more than 2 chars")
              }
              break;
            case "phone":
              if(!this.regexPhone.test(this.employee.phone)){
                console.log("Invalid phone number")
              }
              break;
            case "contact_phone":
              if(!this.regexPhone.test(this.employee.contact_phone)){
                console.log("Invalid contact phone number")
              }
              break;
            case "password":
              if(this.employee.password.length < 5){
                console.log("Password Must be more than 5 chars")
              }
              break;
            case "email":
              if(!this.regexEmail.test(this.employee.email)){
                console.log("Invalid Email")
              }
              break;
            case "national_id":
              if(!this.regexNationalID.test(this.employee.national_id)){
                console.log("Invalid National ID")
              }
              break;
            case "working_hours_day":
              if(this.employee.working_hours_day){
                if(this.employee.working_hours_day > 23){
                  console.log("Invalid working_hours_day")
                }
              }
              break;
          }
        }
      }
    }

    if(this.employee.role.length == 0){
      this.validationErrors["role"] = true;
      isValid = false;
    } else {
      this.validationErrors["role"] = false;
    }

    if(this.employee.start_time != null && this.employee.end_time != null){
      let [xHours, xMinutes] = this.employee.start_time.split(':').map(Number);
      let [yHours, yMinutes] = this.employee.end_time.split(':').map(Number);

      const start_timeDate = new Date();
      const end_timeDate = new Date();

      start_timeDate.setHours(xHours, xMinutes, 0, 0);
      end_timeDate.setHours(yHours, yMinutes, 0, 0);

      const diffMilliseconds = end_timeDate.getTime() - start_timeDate.getTime();

      const diffHours = diffMilliseconds / (1000 * 60 * 60);

      if (diffHours !== this.employee.working_hours_day || diffHours < 0 ) {
        console.log("Invalid Start and end time")
      }
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
