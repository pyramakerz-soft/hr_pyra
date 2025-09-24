import { CommonModule } from '@angular/common';
import { Component, HostListener } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import Swal from 'sweetalert2';
import { AddEmployee } from '../../../Models/add-employee';
import { AssignLocationToUser } from '../../../Models/assign-location-to-user';
import { Department } from '../../../Models/department';
import { RoleModel } from '../../../Models/role-model';
import { Timezone } from '../../../Models/timeZone';
import { WorkType } from '../../../Models/work-type';
import { DepartmentService } from '../../../Services/department.service';
import { LocationsService } from '../../../Services/locations.service';
import { RolesService } from '../../../Services/roles.service';
import { SubDepartmentService } from '../../../Services/sub-department.service';
import { TimeZoneService } from '../../../Services/timezone.service';
import { UserServiceService } from '../../../Services/user-service.service';
import { WorkTypeService } from '../../../Services/work-type.service';
import { DeductionPlan, DeductionRule, ResolvedDeductionPlan } from '../../../Models/deduction-plan';
import { DeductionPlanService } from '../../../Services/deduction-plan.service';
import { DeductionPlanEditor, PLAN_CONDITION_OPTIONS, PLAN_PENALTY_TYPES, PLAN_RULE_CATEGORIES, PLAN_SCOPE_OPTIONS, WEEKDAY_OPTIONS, PlanConditionOption, PlanConditionType, getConditionLabel } from '../../../Helpers/deduction-plan-editor';

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
  workTypes: WorkType[] = [];
  Locations: AssignLocationToUser[] = [];
  isDropdownOpen = false;
  imagePreview: string | ArrayBuffer | null = null;
  isSaved = false
  isFloatChecked: boolean = false;
    timezones: Timezone[] = [];

  planEditor = new DeductionPlanEditor();
  employeePlan: DeductionPlan = this.planEditor.plan;
  effectivePlan?: ResolvedDeductionPlan;
  planConditionOptions = PLAN_CONDITION_OPTIONS;
  ruleCategories = PLAN_RULE_CATEGORIES;
  penaltyTypes = PLAN_PENALTY_TYPES;
  scopeOptions = PLAN_SCOPE_OPTIONS;
  weekdayOptions = WEEKDAY_OPTIONS;
  planLoading = false;
  planSaving = false;
  planEffectiveSources: Array<{ type: string; id: number | string; overwrite: boolean }> = [];

  employee: AddEmployee = new AddEmployee(null,
    null, '', '', null, null, null, '', '', '', '', '', '', null, null, null, null, null, null, '',null, [], [], [], [], false
  );

   regexPhone = /^\d{11,}$/;

  regexEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  regexNationalID = /^\d{14}$/;

  maxFileSize = 15 * 1024 * 1024;

  validationErrors: { [key in keyof AddEmployee]?: string } = {};
  

  SelectDepartment:string="AllDepartment";
  departments:Department[]=[]
  subDepartments: any[] = [];
  
  selectedDepartment: number | null = null;
  selectedSubDepartment: number | null = null;
  selectedTimezone: number | null = null;


  constructor(private route: ActivatedRoute,  
              public roleService: RolesService, 
              public departmentService: DepartmentService,
              public userService: UserServiceService, 
              public workTypeService: WorkTypeService,
              public locationService: LocationsService,
              public timezoneService: TimeZoneService,
              public router: Router,
              public supDeptServ:SubDepartmentService,
              private planService: DeductionPlanService
              
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
this.getTimezones();

    // const stringifiedEmployee = `{ "image":null,"name":"sdas","code":"12321","department_id":1,"sub_department_id":null,"deparment_name":null,"emp_type":"asdas","phone":"‪01117730007‬","contact_phone":"01117730007‬","email":"aanyyy@g.com","password":"111111111","national_id":"11111111111112","hiring_date":"2025-05-07","salary":"1","overtime_hours":"2","working_hours_day":12,"start_time":"10:59","end_time":"22:59","gender":"f","role":{"id":3,"name":"Employee","Permissions":[]},"location_id":[1],"location":[],"work_type_id":[3],"work_type_name":[],"work_home":false}`
    
    // this.employee = JSON.parse(stringifiedEmployee);
    

  }


  getTimezones(){
    this.timezoneService.getAllTimezones().subscribe(
      (timezones: any) => {
        this.timezones = timezones.data || timezones;
      },
      (error) => {
        console.error('Failed to fetch timezones', error);
      }
    );
  }

  onTimezoneChange() {
    this.employee.timezone_id = this.selectedTimezone;
  }

  getRoles(){
    
    this.roleService.getall().subscribe(
      (roles: any) => {
        
        this.roles = roles.roles
        
      }

    );
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










  GetAllDepartment(){
    this.departmentService.getall().subscribe(
      (d: any) => {
        this.departments = d.data.departments;
      }
    );
  }





onDepartmentChange() {

 this.subDepartments = [];

 this.selectedSubDepartment=null;

 if (this.selectedDepartment) {
  this.supDeptServ.setDeptId(this.selectedDepartment);

   this.getSubDepartments(this.selectedDepartment);
 }
}

getSubDepartments(departmentId: number) {
  
 this.supDeptServ.getall (departmentId).subscribe(
   (res: any) => {
     this.subDepartments = res.data || res;
   },
   (err) => {
     console.error('Failed to fetch sub-departments', err);
   }
 );
}

onSubDepartmentChange() {


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
        this.selectedDepartment=this.employee.department_id
        this.selectedSubDepartment=this.employee.sub_department_id
        this.selectedTimezone=this.employee.timezone_id

        if( this.employee.department_id){
          this.supDeptServ.setDeptId(this.employee.department_id!);
          this.getSubDepartments(  this.employee.department_id)

        }
        // this.employee.role = this.employee.role || ''
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

  toggleSingleRole(role: RoleModel) {
    if (this.employee.role === role) {
      this.employee.role = null; // uncheck if already selected
    } else {
      this.employee.role = role; // set selected role
    }

    if (this.employee.role?.name !== 'Employee') {
      this.selectedDepartment = null;
      this.selectedSubDepartment = null;
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
    if(field == "timezone_id"){
      return "Timezone";
    }
    return field.charAt(0).toUpperCase() + field.slice(1).replace(/_/g, ' ');
  }

  isFormValid(): boolean {
    let isValid = true;

  
    // Convert to string for saving/debugging
    const employeeStr = JSON.stringify(this.employee);
  
    for (const key in this.employee) {
      if (this.employee.hasOwnProperty(key)) {
        const field = key as keyof AddEmployee;
  
        if (this.employee.role?.name === 'Employee') {
  
          if (!this.selectedDepartment) {
            this.validationErrors['selectedDepartment' as keyof AddEmployee] = '*Department is required for Employees.';
            isValid = false;
          } else {
            this.validationErrors['selectedDepartment' as keyof AddEmployee] = '';
            isValid = true;
          }
        }




            // Skip validation for department_id and sub_department_id if role is not Employee
            if (this.employee.role?.name !== 'Employee' && (
                field === "deparment_name"||
              field === "department_id" || field === "sub_department_id")) {
              continue; // Skip this field from validation
            }

    // ✅ Add validation: If role is 'Employee', department is required
  if (this.employee.role?.name === 'Employee' && !this.selectedDepartment) {
    this.validationErrors['department_id'] = '*Department is required for employees.';
    isValid = false;
  }

        if (!this.employee[field] && field !== "code" && field !== 'work_home' && field !== "image" &&  field !== "working_hours_day" && field !== "timezone_id") {
  
          if (this.EmployeeId !== 0) {
            continue;
          }
  
          if (field === "start_time" || field === "end_time") {
            if (!this.isFloatChecked) {
              this.validationErrors[field] = `*${this.capitalizeField(field)} is required`;
              isValid = false;
              // Don't show SweetAlert here, it will be shown in the comprehensive validation below
            } else {
              this.validationErrors[field] = '';
            }
          } else {
            this.validationErrors[field] = `*${this.capitalizeField(field)} is required`;
            isValid = false;
          }
        } else {
          this.validationErrors[field] = '';
  
          switch (field) {
            case "name":
              if (this.employee.name.length < 3) {
                this.validationErrors[field] = 'Name must be more than 2 characters.';
                isValid = false;
              }
              break;
            case "code":
              if (this.employee.code.length < 1) {
                this.validationErrors[field] = 'Code is required.';
                isValid = false;
              }
              break;
            case "phone":
              
              const cleanedPhone = this.employee.phone.replace(/[^\d]/g, ''); // keep only digits

              if (!this.regexPhone .test(cleanedPhone)) {
                this.validationErrors[field] = 'Invalid phone number.';
                isValid = false;
              }
              this.employee.phone=cleanedPhone
              break;
            case "contact_phone":
            
              const cleanedContactPhone = this.employee.phone.replace(/[^\d]/g, ''); // keep only digits

              if (!this.regexPhone .test(cleanedContactPhone)) {
                this.validationErrors[field] = 'Invalid phone number.';
                isValid = false;
              }
              this.employee.contact_phone=cleanedContactPhone

              break;
            case "password":
              if (this.employee.password.length < 5 && this.EmployeeId === 0) {
                this.validationErrors[field] = 'Password must be more than 5 characters.';
                isValid = false;
              }
              break;
            case "email":
              if (!this.regexEmail.test(this.employee.email)) {
                this.validationErrors[field] = 'Invalid email.';
                isValid = false;
              }
              break;
            case "national_id":
              if (!this.regexNationalID.test(this.employee.national_id)) {
                this.validationErrors[field] = 'Invalid National ID.';
                isValid = false;
              }
              break;
          }
        }
      }
    }
  
    if (!this.employee.role) {
      this.validationErrors['role'] = '*Role is required.';
      isValid = false;
    } else {
      this.validationErrors['role'] = '';
    }

    // Validate timezone is required
    if (!this.selectedTimezone) {
      this.validationErrors['timezone_id'] = '*Timezone is required.';
      isValid = false;
    } else {
      this.validationErrors['timezone_id'] = '';
    }
  
    if (!this.isFloatChecked) {
  
      if (this.employee.work_type_id.length === 0) {
        this.validationErrors['work_type_id'] = '*Work Type is required.';
        isValid = false;
      } else {
        this.validationErrors['work_type_id'] = '';
      }
  
      if (this.employee.location_id.length === 0) {
        this.validationErrors['location_id'] = '*Location is required.';
        isValid = false;
      } else {
        this.validationErrors['location_id'] = '';
      }

      // Validate start_time and end_time are provided when not float
      if (!this.employee.start_time || !this.employee.end_time) {
        if (!this.employee.start_time) {
          this.validationErrors['start_time'] = '*Start time is required.';
        }
        if (!this.employee.end_time) {
          this.validationErrors['end_time'] = '*End time is required.';
        }
        isValid = false;
        Swal.fire({
          icon: "error",
          title: "Missing Time Information",
          text: "Both start time and end time are required for non-float employees.",
          confirmButtonText: "OK",
          confirmButtonColor: "#FF7519",
        });
      } else if (this.employee.start_time && this.employee.end_time) {
        // Clear validation errors if both times are provided
        this.validationErrors['start_time'] = '';
        this.validationErrors['end_time'] = '';

        let [xHours, xMinutes] = this.employee.start_time.split(':').map(Number);
        let [yHours, yMinutes] = this.employee.end_time.split(':').map(Number);
  
        const start_timeDate = new Date();
        const end_timeDate = new Date();
  
        start_timeDate.setHours(xHours, xMinutes, 0, 0);
        end_timeDate.setHours(yHours, yMinutes, 0, 0);
  
        const diffMilliseconds = end_timeDate.getTime() - start_timeDate.getTime();
        const diffHours = diffMilliseconds / (1000 * 60 * 60);

        // Handle case where end time is on the next day
        if (diffHours < 0) {
          // Add 24 hours if end time is next day
          const adjustedDiffHours = diffHours + 24;
          if (adjustedDiffHours < 4) {
            isValid = false;
            Swal.fire({
              icon: "warning",
              title: "Invalid Working Hours",
              text: "Working hours must be at least 4 hours.",
              confirmButtonText: "OK",
              confirmButtonColor: "#FF7519",
            });
          } else {
            this.employee.working_hours_day = adjustedDiffHours;
          }
        } else if (diffHours < 4) {
          isValid = false;
          Swal.fire({
            icon: "warning",
            title: "Invalid Working Hours",
            text: "Working hours must be at least 4 hours.",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",
          });
        } else {
          this.employee.working_hours_day = diffHours;
        }

        // Validate that start time is different from end time
        if (this.employee.start_time === this.employee.end_time) {
          isValid = false;
          Swal.fire({
            icon: "error",
            title: "Invalid Time Range",
            text: "Start time and end time cannot be the same.",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",
          });
        }
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
        this.isSaved = true;
        this.employee.department_id =this.selectedDepartment==null? null:Number(this.selectedDepartment);
        this.employee.sub_department_id =this.selectedSubDepartment==null?null: Number(this.selectedSubDepartment);
        this.employee.timezone_id = this.selectedTimezone;



        // Log the payload for debugging
        console.log('Employee Payload:', this.employee);

        if (this.EmployeeId === 0) {
            this.userService.createUser(this.employee).subscribe(
                (result: any) => {
                    this.isSaved = false;
                    Swal.fire({
                        icon: "success",
                        title: "Employee Created Successfully!",
                        text: "The employee has been added to the system.",
                        confirmButtonText: "OK",
                        confirmButtonColor: "#FF7519",
                    }).then(() => {
                        this.router.navigateByUrl("HR/HREmployee");
                    });
                },
                error => {
                    this.isSaved = false;
                    console.error('Create User Error:', error); // Log the full error
                    this.handleServerErrors(error.error?.errors || {});
                }
            );
        } else {
            this.userService.updateUser(this.employee, this.EmployeeId).subscribe(
                (result: any) => {
                    this.isSaved = false;
                    Swal.fire({
                        icon: "success",
                        title: "Employee Updated Successfully!",
                        text: "The employee information has been updated.",
                        confirmButtonText: "OK",
                        confirmButtonColor: "#FF7519",
                    }).then(() => {
                        this.router.navigateByUrl("HR/HREmployee");
                    });
                },
                error => {
                  this.isSaved = false;

                    console.error('Update User Error:', error); // Log the full error
                    this.handleServerErrors(error.error?.errors || {});
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
