import { Component } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { RolesService } from '../../../Services/roles.service';
import { RoleModel } from '../../../Models/role-model';
import { DepartmentService } from '../../../Services/department.service';
import { Department } from '../../../Models/department';
import { AddEmployee } from '../../../Models/add-employee';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { UserServiceService } from '../../../Services/user-service.service';
import Swal from 'sweetalert2'

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

  // validationErrors: { [key in keyof AddEmployee]?: boolean | string } = {};
  validationErrors: { [key in keyof AddEmployee]?: string } = {};
  
  constructor(private route: ActivatedRoute,  
              public roleService: RolesService, 
              public departmentService: DepartmentService,
              public userService: UserServiceService, 
              public router: Router
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
      }
    );
  }

  getDepartments(){
    this.departmentService.getall().subscribe(
      (departments: any) => {
        this.departments = departments.data.departments
      }
    );
  }

  getRoles(){
    this.roleService.getall().subscribe(
      (roles: any) => {
        this.roles = roles.roles
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

    if (this.employee.role.length > 0) {
      this.validationErrors['role'] = '';
    } else {
      this.validationErrors['role'] = '*Role is required.';
    }
  }

  capitalizeField(field: keyof AddEmployee): string {
    if(field == "emp_type"){
      return "Position";
    }
    return field.charAt(0).toUpperCase() + field.slice(1).replace(/_/g, ' ');
  }

  isFormValid(): boolean {
    let isValid = true;

    for (const key in this.employee) {
      if (this.employee.hasOwnProperty(key)) {
        const field = key as keyof AddEmployee;
        if (!this.employee[field] && field != "code") {
          this.validationErrors[field] = `*${this.capitalizeField(field)} is required.`;
          isValid = false;
        } else {
          this.validationErrors[field] = '';

          switch (field){
            case "name":
              if(this.employee.name.length < 3){
                this.validationErrors[field] = 'Name must be more than 2 characters.';
                isValid = false;
              }
              break;
            case "phone":
              if(!this.regexPhone.test(this.employee.phone)){
                this.validationErrors[field] = 'Invalid phone number.';
                isValid = false;
              }
              break;
            case "contact_phone":
              if(!this.regexPhone.test(this.employee.contact_phone)){
                this.validationErrors[field] = 'Invalid contact phone number.';
                isValid = false;
              }
              break;
            case "password":
              if(this.employee.password.length < 5){
                this.validationErrors[field] = 'Password must be more than 5 characters.';
                isValid = false;
              }
              break;
            case "email":
              if(!this.regexEmail.test(this.employee.email)){
                this.validationErrors[field] = 'Invalid email.';
                isValid = false;
              }
              break;
            case "national_id":
              if(!this.regexNationalID.test(this.employee.national_id)){
                this.validationErrors[field] = 'Invalid National ID.';
                isValid = false;
              }
              break;
            case "working_hours_day":
              if(this.employee.working_hours_day){
                if(this.employee.working_hours_day > 23){
                  this.validationErrors[field] = 'Invalid working hours day.';
                  isValid = false;
                }
              }
              break;
          }
        }
      }
    }

    if(this.employee.role.length == 0){
      this.validationErrors['role'] = '*Role is required.';
      isValid = false;
    } else {
      this.validationErrors['role'] = '';
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

      const workingHoursDay = this.employee.working_hours_day != null ? this.employee.working_hours_day : 0; 

      if (diffHours - parseFloat(workingHoursDay.toString()) > 0.01 || diffHours < 0 ) {
        this.validationErrors['start_time'] = 'Invalid Start Time.';
        this.validationErrors["end_time"] = 'Invalid End Time.';
        this.validationErrors['working_hours_day'] = 'Invalid Working hours day.';
        isValid = false;
        Swal.fire({
          icon: "error",
          title: "Invalid Input",
          text: "Starting Time and Ending Time not Compatible with Working hours day",
          confirmButtonText: "OK",
          confirmButtonColor: "#FF7519",
          
        });
      }
    }

    return isValid;
  }

  onInputValueChange(event: { field: keyof AddEmployee, value: any }) {
    const { field, value } = event;
    if (field in this.employee) {
      (this.employee as any)[field] = value;
      if (value) {
        this.validationErrors[field] = '';
      }
    }
  }
  
  SaveEmployee() {

    if (this.isFormValid()) {
      this.employee.department_id = Number(this.employee.department_id);

      if(this.EmployeeId === 0){
        this.userService.createUser(this.employee).subscribe(
          (result: any) => {
            this.router.navigateByUrl("HR/HREmployee")
          },
          error => {
            if (error.error && error.error.errors) {
              this.handleServerErrors(error.error.errors as Record<keyof AddEmployee, string[]>);
            }
          }
        );
      } else{
        this.userService.updateUser(this.employee, this.EmployeeId).subscribe(
          (result: any) => {
            this.router.navigateByUrl("HR/HREmployee")
          },
          error => {
            if (error.error && error.error.errors) {
              this.handleServerErrors(error.error.errors as Record<keyof AddEmployee, string[]>);
            }
          }
        );
      }
    }
  }

  private handleServerErrors(errors: Record<keyof AddEmployee, string[]>) {
    for (const key in errors) {
      if (errors.hasOwnProperty(key)) {
        const field = key as keyof AddEmployee; 
        this.validationErrors[field] = errors[field].join(' ');
      }
    }
  }
}
