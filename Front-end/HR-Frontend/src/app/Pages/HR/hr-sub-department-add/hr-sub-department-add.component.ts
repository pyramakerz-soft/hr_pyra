import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import Swal from 'sweetalert2';
import { TeamLead } from '../../../Models/teamLead';
import { SubDepartmentService } from '../../../Services/sub-department.service';
import { TeamLeadsService } from '../../../Services/team-leads.service';
import { DeductionPlan, DeductionRule } from '../../../Models/deduction-plan';
import { DeductionPlanService } from '../../../Services/deduction-plan.service';
import { DeductionPlanEditor, PLAN_CONDITION_OPTIONS, PLAN_PENALTY_TYPES, PLAN_RULE_CATEGORIES, PLAN_SCOPE_OPTIONS, WEEKDAY_OPTIONS, PlanConditionOption, PlanConditionType, getConditionLabel } from '../../../Helpers/deduction-plan-editor';

@Component({
  selector: 'app-hr-department-add',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './hr-sub-department-add.component.html',
  styleUrl: './hr-sub-department-add.component.css'
})
export class HrSubDepartmentAddComponent {

  TeamLeadNames: TeamLead[] = [];
  nameSelected: string = ""
  isDropdownOpen: boolean = false
  DeptName: string = ""
  mode: string = ""
  DeptId: number = 1;
  subDeptId: number = 1;


  DeptNameError: string = ""; 
  TeamLeadError: string = ""; 

  SaveButton:boolean=false;

  NotifyError: string = ""; 

  planEditor = new DeductionPlanEditor();
  subDepartmentPlan: DeductionPlan = this.planEditor.plan;
  planConditionOptions = PLAN_CONDITION_OPTIONS;
  ruleCategories = PLAN_RULE_CATEGORIES;
  penaltyTypes = PLAN_PENALTY_TYPES;
  scopeOptions = PLAN_SCOPE_OPTIONS;
  weekdayOptions = WEEKDAY_OPTIONS;
  planLoading = false;
  planSaving = false;
  showPlanEditor = false;
  locationTypeOptions = [
    { value: 'site', label: 'On Site' },
    { value: 'home', label: 'Home' },
    { value: 'float', label: 'Float' },
  ];

  constructor(public teamLeadSer: TeamLeadsService, public subDeptSer: SubDepartmentService, private router: Router, private route: ActivatedRoute, private planService: DeductionPlanService) { }


  ngOnInit() {

    this.route.params.subscribe(params => {
      console.log(params);

      if (params['deptId'] && params['subDeptId']) {
        console.log('/ssss');

        this.DeptId = params['deptId'];
        this.subDeptId = params['subDeptId'];

        this.subDeptSer.setDeptId(this.DeptId);

        this.mode = "Edit";

        this.initializePlan();
        this.loadPlan();
        this.GetByID();
      } else if (params['deptId']) {
        this.DeptId = params['deptId'];
        console.log(this.DeptId);

        this.subDeptSer.setDeptId(this.DeptId);

        this.mode = "Add";
        this.initializePlan();
      } else {
        this.mode = "Add";
        this.initializePlan();
      }
    });


    this.getTeamLeadsNames();
    localStorage.setItem('HrEmployeeCN', "1");
    localStorage.setItem('HrLocationsCN', "1");
    localStorage.setItem('HrAttendaceCN', "1");


  }

  goBack() {
    this.router.navigateByUrl(`/HR/HRSubDepartment/${this.DeptId}`);
  }

  getTeamLeadsNames() {
    this.teamLeadSer.getall().subscribe(
      (d: any) => {
        // console.log(d);  // Add this log
        this.TeamLeadNames =d.teamLeadNames
   

      }
    );
  }

  toggleDropdown() {
    this.isDropdownOpen = !this.isDropdownOpen;
  }

  selectTeamLead(teamLead: TeamLead): void {
    this.nameSelected = teamLead.team_lead_name;
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
        this.TeamLeadError = "";  
        if (this.DeptName == "" && this.nameSelected == "" ) {
          this.DeptNameError = '*Department Name Can not be empty';
          this.TeamLeadError = '*Choose a TeamLead';
        } else if (this.DeptName == "" ) {
          this.DeptNameError = '*Department Name Can not be empty';
        } else if (this.nameSelected == "") {
          this.TeamLeadError = '*Choose a TeamLead';
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

    const manager = this.TeamLeadNames.find(manager => manager.team_lead_name === this.nameSelected);
    if (manager) {
      const teamLead_id = manager.team_lead_id;
    

      this.subDeptSer.createDepartment(this.DeptName, teamLead_id ).subscribe(
        (response: any) => {
          this.router.navigateByUrl("/HR/HRSubDepartment/" + this.DeptId);


        },
        (error: any) => {
          this.SaveButton=false;
          if (error.error.message === "The name has already been taken.") {
            Swal.fire({   
              text: "The Sub Departmen name has already been taken",
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
        text: "No TeamLead found with the selected name",
        confirmButtonText: "OK",
        confirmButtonColor: "#FF7519",

      });
    }
  }


GetByID(){
  this.subDeptSer.GetByID(this.subDeptId).subscribe(
    (d: any) => {
      console.log('/////');
      
      console.log(d);
      
      this.DeptName = d.data.name;
      this.nameSelected = d.data.team_lead.name;
    }
  );
}

cancel(){
  this.router.navigateByUrl("/HR/HRSubDepartment/" + this.DeptId);

}

UpdateDepartment(){
  const teamLead = this.TeamLeadNames.find(teamLead => teamLead.team_lead_name === this.nameSelected);
  if (teamLead) {
    const teamLeadId = teamLead.team_lead_id;

    this.subDeptSer.UpdateDept(this.subDeptId, this.DeptName, teamLeadId ).subscribe(
      (response: any) => {
        this.router.navigateByUrl("/HR/HRSubDepartment/" + this.DeptId);



      },
      (error: any) => {
        this.SaveButton=false;
        if (error.error.message === "The name has already been taken.") {
          Swal.fire({   
            text: "The Sub Department name has already been taken",
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
    this.SaveButton = false;
    Swal.fire({
      text: 'No manager found with the selected name',
      confirmButtonText: 'OK',
      confirmButtonColor: '#FF7519',
    });
  }

  }

  private initializePlan(plan?: DeductionPlan): void {
    this.planEditor.setPlan(plan);
    this.subDepartmentPlan = this.planEditor.plan;
  }

  togglePlanSection(): void {
    if (this.mode !== 'Edit') {
      Swal.fire({
        text: 'Save the sub-department details before configuring a plan.',
        confirmButtonText: 'OK',
        confirmButtonColor: '#FF7519',
      });
      return;
    }

    this.showPlanEditor = !this.showPlanEditor;
  }

  isLocationTypeCondition(key: string): boolean {
    return key === 'location_type_in' || key === 'location_type_not_in';
  }

  isWorkTypeCondition(key: string): boolean {
    return key === 'work_type_in' || key === 'work_type_not_in';
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

  onOverwriteChange(value: boolean): void {
    this.planEditor.setOverwrite(value);
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
    if (this.mode !== 'Edit' || !this.subDeptId) {
      return;
    }

    this.planLoading = true;
    this.planService.getSubDepartmentPlan(this.subDeptId).subscribe({
      next: (plan) => {
        this.initializePlan(plan);
        this.planLoading = false;
        this.showPlanEditor = true;
      },
      error: () => {
        this.planLoading = false;
      },
    });
  }

  savePlan(): void {
    if (this.mode !== 'Edit' || !this.subDeptId) {
      Swal.fire({
        text: 'Save the sub-department details before configuring a plan.',
        confirmButtonText: 'OK',
        confirmButtonColor: '#FF7519',
      });
      return;
    }

    this.planSaving = true;
    this.planService.saveSubDepartmentPlan(this.subDeptId, this.planEditor.plan).subscribe({
      next: (plan) => {
        this.initializePlan(plan);
        this.planSaving = false;
        Swal.fire({
          icon: 'success',
          text: 'Sub-department deduction plan saved successfully.',
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
