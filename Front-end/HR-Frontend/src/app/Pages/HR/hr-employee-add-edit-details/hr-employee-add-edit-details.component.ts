import { Component, HostListener } from '@angular/core';
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
import { WorkTypeService } from '../../../Services/work-type.service';
import { WorkType } from '../../../Models/work-type';
import { AssignLocationToUser } from '../../../Models/assign-location-to-user';
import { LocationsService } from '../../../Services/locations.service';

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
  workTypes: WorkType[] = [];
  Locations: AssignLocationToUser[] = [];
  isDropdownOpen = false;
  imagePreview: string | ArrayBuffer | null = null;
  isSaved = false
  isFloatChecked: boolean = false;
  
  employee: AddEmployee = new AddEmployee(
    null, '', '', null, null, '', '', '', '', '', '', null, null, null, null, null, null, '', [], [], [], [], [], false
  );

  regexPhone = /^(010|011|012|015)\d{8}$/;
  regexEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  regexNationalID = /^\d{14}$/;

  maxFileSize = 15 * 1024 * 1024;

  validationErrors: { [key in keyof AddEmployee]?: string } = {};
  
  constructor(private route: ActivatedRoute,  
              public roleService: RolesService, 
              public departmentService: DepartmentService,
              public userService: UserServiceService, 
              public workTypeService: WorkTypeService,
              public locationService: LocationsService,
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
    this.getWorkType()
    this.getLocations()
  }

  toggleDropdown(event: MouseEvent) {
    event.stopPropagation(); // Prevent the click event from bubbling up
    this.isDropdownOpen = !this.isDropdownOpen;
  }

  // Close dropdown if clicked outside
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

  filterNumericInput(event: Event) {
    const input = event.target as HTMLInputElement;
    let previousValue = input.value;

    input.addEventListener('input', function() {
        let newValue = input.value.replace(/[^0-9.]/g, '');

        if (newValue.split('.').length > 2) {
            input.value = previousValue; 
        } else {
            input.value = newValue; 
            previousValue = input.value; 
        }
    });
  }  

  removeFromLocations(locationID:number, event: MouseEvent){
    event.stopPropagation();
    this.employee.location_id = this.employee.location_id.filter(locarion_Id => locarion_Id !== locationID);
  }
  
  getEmployeeByID(id:number){
    this.userService.getUserById(id).subscribe(
      (d: any) => {
        this.employee = d.User;
        this.employee.roles = this.employee.roles || []
        // this.employee.is_float == 1 ? this.isFloatChecked = true : this.isFloatChecked = false 
        if(typeof this.employee.image == "string"){
          this.imagePreview = this.employee.image
        }
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
  
  getWorkType(){
    this.workTypeService.getall().subscribe(
      (workTypes: any) => {
        this.workTypes = workTypes.workTypes
      }
    );
  }
  
  getLocations(){
    this.locationService.GetAllNames().subscribe(
      (locations: any) => {
        this.Locations = locations.locationNames
      } 
    );
  }

  onLocationChange(Location: number, event: Event) {
    const isChecked = (event.target as HTMLInputElement).checked;

    if (isChecked) {
      if (!this.employee.location_id.includes(Location)) {
        this.employee.location_id.push(Location);
      }
    } else {
      const index = this.employee.location_id.indexOf(Location);
      if (index > -1) {
        this.employee.location_id.splice(index, 1);
      }
    }

    if(!this.isFloatChecked){
      if (this.employee.location_id.length > 0) {
        this.validationErrors['location_id'] = '';
      } else {
        this.validationErrors['location_id'] = '*Location is required.';
      }
    }
  }

  onWorkTypeChange(WorkType: number, event: Event) {
    const isChecked = (event.target as HTMLInputElement).checked;

    if (isChecked) {
      if (!this.employee.work_type_id.includes(WorkType)) {
        this.employee.work_type_id.push(WorkType);
      }
    } else {
      const index = this.employee.work_type_id.indexOf(WorkType);
      if (index > -1) {
        this.employee.work_type_id.splice(index, 1);
      }
    }

    if(!this.isFloatChecked){
      if (this.employee.work_type_id.length > 0) {
        this.validationErrors['work_type_id'] = '';
      } else {
        this.validationErrors['work_type_id'] = '*Work Type is required.';
      }
    }
  }
  
  // onIsFloatChange(event: Event){
  //   this.isFloatChecked = !this.isFloatChecked;
  //   if(this.isFloatChecked){
  //     this.employee.is_float = 1
  //   } else{
  //     this.employee.is_float = 0
  //   }
  // }

  onRoleChange(roleName: string, event: Event) {
    const isChecked = (event.target as HTMLInputElement).checked;

    if (isChecked) {
      if (!this.employee.roles.includes(roleName)) {
        this.employee.roles.push(roleName);
      }
    } else {
      const index = this.employee.roles.indexOf(roleName);
      if (index > -1) {
        this.employee.roles.splice(index, 1);
      }
    }

    if (this.employee.roles.length > 0) {
      this.validationErrors['roles'] = '';
    } else {
      this.validationErrors['roles'] = '*Role is required.';
    }
  }

  onImageFileSelected(event: any) {
    const file: File = event.target.files[0];
    
    if (file) {
      if (file.size > this.maxFileSize) {
        this.validationErrors['image'] = 'The file size exceeds the maximum limit of 15 MB.';
        this.imagePreview = null;
        this.employee.image = null;
        return; 
      }
      if (file.type === 'image/jpeg' || file.type === 'image/png') {
        this.employee.image = file; 
        this.validationErrors['image'] = ''; 

        const reader = new FileReader();
        reader.onload = () => {
          this.imagePreview = reader.result;
        };
        reader.readAsDataURL(file);
      } else {
        this.validationErrors['image'] = 'Invalid file type. Only JPEG, JPG and PNG are allowed.';
        this.imagePreview = null;
        this.employee.image = null;
        return; 
      }
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
        // && field != "is_float"
        if (!this.employee[field] && field != "code" && field !='work_home' && field != "image" && field != "deparment_name" && field != "working_hours_day") {
          if(this.EmployeeId !== 0){
            continue
          }
          if(field=="start_time" || field=="end_time"){
            if(!this.isFloatChecked){
              this.validationErrors[field] = `*${this.capitalizeField(field)} is required`
              isValid = false;
            }else{
              this.validationErrors[field] = '';
            }
          }else{
            this.validationErrors[field] = `*${this.capitalizeField(field)} is required`
            isValid = false;
          }
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
              if(this.employee.password.length < 5 && this.EmployeeId === 0){
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
            // case "working_hours_day":
            //   if(this.employee.working_hours_day){
            //     if(this.employee.working_hours_day > 23){
            //       this.validationErrors[field] = 'Invalid working hours day.';
            //       isValid = false;
            //     }
            //   }
            //   break;
          }
        }
      }
    }

    if(this.employee.roles.length == 0){
      this.validationErrors['roles'] = '*Role is required.';
      isValid = false;
    } else {
      this.validationErrors['roles'] = '';
    }

    if(!this.isFloatChecked){
      if(this.employee.work_type_id.length == 0){
        this.validationErrors['work_type_id'] = '*Work Type is required.';
        isValid = false;
      } else {
        this.validationErrors['work_type_id'] = '';
      }
      
      if(this.employee.location_id.length == 0){
        this.validationErrors['location_id'] = '*Location is required.';
        isValid = false;
      } else {
        this.validationErrors['location_id'] = '';
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
        if(diffHours < 4){
          isValid = false;
          Swal.fire({
            icon: "warning",
            title: "Working Hours must be at least 4",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",
          })
        }else{
          this.employee.working_hours_day = diffHours
        }
        const workingHoursDay = this.employee.working_hours_day != null ? this.employee.working_hours_day : 0; 
  
        // if (diffHours - parseFloat(workingHoursDay.toString()) > 0 || diffHours - parseFloat(workingHoursDay.toString()) < 0 || diffHours < 0 ) {
        //   this.validationErrors['start_time'] = 'Invalid Start Time.';
        //   this.validationErrors["end_time"] = 'Invalid End Time.';
        //   this.validationErrors['working_hours_day'] = 'Invalid Working hours day.';
        //   isValid = false;
        //   Swal.fire({
        //     icon: "error",
        //     title: "Invalid Input",
        //     text: "Starting Time and Ending Time not Compatible with Working hours day",
        //     confirmButtonText: "OK",
        //     confirmButtonColor: "#FF7519",
            
        //   });
        // }
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
      this.isSaved = true
      this.employee.department_id = Number(this.employee.department_id);
      if(this.EmployeeId === 0){
        this.userService.createUser(this.employee).subscribe(
          (result: any) => {
            this.isSaved = false
            this.router.navigateByUrl("HR/HREmployee")
          },
          error => {
            if (error.error && error.error.errors) {
              this.isSaved = false
              this.handleServerErrors(error.error.errors as Record<keyof AddEmployee, string[]>);
            }else{
              Swal.fire({
                icon: "error",
                title: "Server Error, try in another time",
                confirmButtonText: "OK",
                confirmButtonColor: "#FF7519",
              })
            }
          }
        );
      } else{
        this.userService.updateUser(this.employee, this.EmployeeId).subscribe(
          (result: any) => {
            this.isSaved = false
            this.router.navigateByUrl("HR/HREmployee")
          },
          error => {
            if (error.error && error.error.errors) {
              this.isSaved = false
              this.handleServerErrors(error.error.errors as Record<keyof AddEmployee, string[]>);
            }else{
              Swal.fire({
                icon: "error",
                title: "Server Error, try in another time",
                confirmButtonText: "OK",
                confirmButtonColor: "#FF7519",
              })
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