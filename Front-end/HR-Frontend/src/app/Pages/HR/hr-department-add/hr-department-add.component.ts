import { Component } from '@angular/core';
import { ManagersService } from '../../../Services/managers.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Manager } from '../../../Models/manager';
import { DepartmentService } from '../../../Services/department.service';
import { ActivatedRoute, Router } from '@angular/router';
import Swal from 'sweetalert2';
import { DeductionPlan, DeductionRule } from '../../../Models/deduction-plan';
import { DeductionPlanService } from '../../../Services/deduction-plan.service';
import { DeductionPlanEditor, PLAN_CONDITION_OPTIONS, PLAN_PENALTY_TYPES, PLAN_RULE_CATEGORIES, PLAN_SCOPE_OPTIONS, WEEKDAY_OPTIONS, PlanConditionOption, PlanConditionType, getConditionLabel } from '../../../Helpers/deduction-plan-editor';

@Component({
  selector: 'app-hr-department-add',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './hr-department-add.component.html',
  styleUrl: './hr-department-add.component.css'
})
export class HrDepartmentAddComponent {

  ManagerNames: Manager[] = [];
  nameSelected: string = ""
  isDropdownOpen: boolean = false
  DeptName: string = ""
  mode: string = ""
  DeptId: number = 1;
  
  is_location_time :boolean =true
  workScheduleType: 'flexible' | 'strict' = 'flexible';
  worksOnSaturday = false;

  planEditor = new DeductionPlanEditor();
  departmentPlan: DeductionPlan = this.planEditor.plan;
  planConditionOptions = PLAN_CONDITION_OPTIONS;
  ruleCategories = PLAN_RULE_CATEGORIES;
  penaltyTypes = PLAN_PENALTY_TYPES;
  scopeOptions = PLAN_SCOPE_OPTIONS;
  weekdayOptions = WEEKDAY_OPTIONS;
  planLoading = false;
  planSaving = false;

  DeptNameError: string = ""; 
  ManagerError: string = ""; 

  SaveButton:boolean=false;

  NotifyError: string = ""; 
  is_location_timeNum :number=1;

  constructor(public managerServ: ManagersService, public departmentServ: DepartmentService, private router: Router, private route: ActivatedRoute, private planService: DeductionPlanService) { }


  ngOnInit() {

    this.route.params.subscribe(params => {
      if (params['id']) {
        this.DeptId = params['id'];
        this.mode = "Edit";
        this.GetByID(this.DeptId);
      } else {
        this.mode = "Add";
        this.initializePlan();
      }
    });

    this.getMnagerNames();
    localStorage.setItem('HrEmployeeCN', "1");
    localStorage.setItem('HrLocationsCN', "1");
    localStorage.setItem('HrAttendaceCN', "1");


  }
  getMnagerNames() {
    this.managerServ.getall().subscribe(
      (d: any) => {
        this.ManagerNames = d.managerNames;
      }
    );
  }

  toggleDropdown() {
    this.isDropdownOpen = !this.isDropdownOpen;
  }

  selectManager(manager: Manager): void {
    this.nameSelected = manager.manager_name;
    this.isDropdownOpen = false; // Close dropdown after selection
  }

  Save() {
    if (this.nameSelected == "" || this.DeptName == "" ) {
      // Swal.fire({
      //   text: "Complete all required fields.",
      //   confirmButtonText: "OK",
      //   confirmButtonColor: "#FF7519",

      // });
        this.DeptNameError = ""; 
        this.ManagerError = "";  
        if (this.DeptName == "" && this.nameSelected == "" ) {
          this.DeptNameError = '*Department Name Can not be empty';
          this.ManagerError = '*Choose a Manager';
        } else if (this.DeptName == "" ) {
          this.DeptNameError = '*Department Name Can not be empty';
        } else if (this.nameSelected == "") {
          this.ManagerError = '*Choose a Manager';
        } 
      }
    else {
      this.SaveButton=true;
      if (this.mode == "Edit") {
        this.UpdateDepartment();
      }
      else if (this.mode == "Add") {
        this.CreateDepartment();

      }
    }
  }


  CreateDepartment(): void {

    const manager = this.ManagerNames.find(manager => manager.manager_name === this.nameSelected);
    if (manager) {
      const ManagerId = manager.manager_id;
      if (this.is_location_time){this.is_location_timeNum=1}
      else{
        this.is_location_timeNum=0
      }

      this.departmentServ.createDepartment(this.DeptName, ManagerId, this.is_location_timeNum, this.workScheduleType, this.worksOnSaturday).subscribe(
        (response: any) => {
          this.router.navigateByUrl("/HR/HRDepartment");

        },
        (error: any) => {
          this.SaveButton=false;
          if (error.error.message === "The name has already been taken.") {
            Swal.fire({   
              text: "The Department name has already been taken",
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
    } else {
      this.SaveButton=false;
      Swal.fire({
        text: "No manager found with the selected name",
        confirmButtonText: "OK",
        confirmButtonColor: "#FF7519",

      });
    }
  }


GetByID(id: number){
  this.departmentServ.GetByID(id).subscribe(
    (d: any) => {
      const department = d.department ?? d.data?.department ?? d.data;
      if (department) {
        this.DeptName = department.name ?? '';
        this.nameSelected = department.manager_name ?? '';
        this.is_location_time = !!department.is_location_time;
        this.is_location_timeNum = this.is_location_time ? 1 : 0;
        const type = (department.work_schedule_type ?? 'flexible').toLowerCase();
        this.workScheduleType = type === 'strict' ? 'strict' : 'flexible';
        this.worksOnSaturday = !!department.works_on_saturday;
      }
      this.initializePlan();
      this.loadPlan();
    }
  );
}

cancel(){
  this.router.navigateByUrl("/HR/HRDepartment");

}

UpdateDepartment(){
  const manager = this.ManagerNames.find(manager => manager.manager_name === this.nameSelected);
  if (manager) {
    const ManagerId = manager.manager_id;
    if (this.is_location_time){this.is_location_timeNum=1}
    else{
      this.is_location_timeNum=0
    }
    this.departmentServ.UpdateDept(this.DeptId, this.DeptName, ManagerId, this.is_location_timeNum, this.workScheduleType, this.worksOnSaturday).subscribe(
      (response: any) => {
        this.router.navigateByUrl("/HR/HRDepartment");

      },
      (error: any) => {
        this.SaveButton=false;
        if (error.error.message === "The name has already been taken.") {
          Swal.fire({   
            text: "The Department name has already been taken",
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
  } else {
    this.SaveButton=false;
    Swal.fire({
      text: "No manager found with the selected name",
      confirmButtonText: "OK",
      confirmButtonColor: "#FF7519",

    });
  }
}


  private initializePlan(plan?: DeductionPlan): void {
    this.planEditor.setPlan(plan);
    this.departmentPlan = this.planEditor.plan;
  }

  addRule(): void {
    this.planEditor.addRule();
  }

  removeRule(index: number): void {
    if (this.mode !== 'Edit') {
      return;
    }

    this.planEditor.removeRule(index);
  }

  updateGraceMinutes(value: any): void {
    this.planEditor.updateGraceMinutes(value);
  }

  getConditionEntries(rule: DeductionRule): Array<{ key: string; value: any }> {
    return this.planEditor.getConditionEntries(rule);
  }

  getConditionLabel(key: string): string {
    return getConditionLabel(key);
  }

  getConditionHint(key: string): string | undefined {
    return this.planConditionOptions.find((option) => option.key === key)?.hint;
  }

  getConditionType(key: string): PlanConditionType {
    return this.planConditionOptions.find((option) => option.key === key)?.type ?? 'string';
  }

  getAvailableConditionOptions(rule: DeductionRule): PlanConditionOption[] {
    return this.planEditor.getAvailableConditionOptions(rule);
  }

  onSelectCondition(ruleIndex: number, key: string | null): void {
    this.planEditor.setSelectedCondition(ruleIndex, key);
  }

  addSelectedCondition(ruleIndex: number): void {
    if (this.mode !== 'Edit') {
      return;
    }

    this.planEditor.addSelectedCondition(ruleIndex);
  }

  removeCondition(ruleIndex: number, key: string): void {
    if (this.mode !== 'Edit') {
      return;
    }

    this.planEditor.removeCondition(ruleIndex, key);
  }

  onConditionValueChange(ruleIndex: number, key: string, value: any): void {
    this.planEditor.updateConditionValue(ruleIndex, key, value);
  }

  getSelectedCondition(ruleIndex: number): string | null {
    return this.planEditor.selectedConditions[ruleIndex] ?? null;
  }

  getCustomDraft(ruleIndex: number): { key: string; value: string } {
    return this.planEditor.getCustomDraft(ruleIndex);
  }

  updateCustomDraftKey(ruleIndex: number, value: string): void {
    this.planEditor.getCustomDraft(ruleIndex).key = value;
  }

  updateCustomDraftValue(ruleIndex: number, value: string): void {
    this.planEditor.getCustomDraft(ruleIndex).value = value;
  }

  addCustomCondition(ruleIndex: number): void {
    if (this.mode !== 'Edit') {
      return;
    }

    this.planEditor.addCustomCondition(ruleIndex);
  }

  asArray(value: any): string[] {
    if (Array.isArray(value)) {
      return value;
    }

    if (value === null || value === undefined || value === '') {
      return [];
    }

    return [value];
  }

  loadPlan(): void {
    if (this.mode !== 'Edit' || !this.DeptId) {
      return;
    }

    this.planLoading = true;
    this.planService.getDepartmentPlan(this.DeptId).subscribe({
      next: (plan) => {
        this.initializePlan(plan);
        this.planLoading = false;
      },
      error: () => {
        this.planLoading = false;
      },
    });
  }

  savePlan(): void {
    if (this.mode !== 'Edit' || !this.DeptId) {
      Swal.fire({
        text: 'Save the department details before configuring a plan.',
        confirmButtonText: 'OK',
        confirmButtonColor: '#FF7519',
      });
      return;
    }

    this.planSaving = true;
    this.planService.saveDepartmentPlan(this.DeptId, this.planEditor.plan).subscribe({
      next: (plan) => {
        this.initializePlan(plan);
        this.planSaving = false;
        Swal.fire({
          icon: 'success',
          text: 'Department deduction plan saved successfully.',
          confirmButtonText: 'OK',
          confirmButtonColor: '#17253E',
        });
      },
      error: () => {
        this.planSaving = false;
        Swal.fire({
          icon: 'error',
          text: 'Failed to save the plan. Please try again later.',
          confirmButtonText: 'OK',
          confirmButtonColor: '#FF7519',
        });
      },
    });
  }

  trackRuleByIndex(index: number): number {
    return index;
  }

  trackCondition(_index: number, item: { key: string }): string {
    return item.key;
  }
}

