import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import Swal from 'sweetalert2';
import { TeamLead } from '../../../Models/teamLead';
import { SubDepartmentService } from '../../../Services/sub-department.service';
import { TeamLeadsService } from '../../../Services/team-leads.service';

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
  

  DeptNameError: string = ""; 
  TeamLeadError: string = ""; 

  SaveButton:boolean=false;

  NotifyError: string = ""; 

  constructor(public teamLeadSer: TeamLeadsService, public subDeptSer: SubDepartmentService, private router: Router, private route: ActivatedRoute) { }


  ngOnInit() {

    this.route.params.subscribe(params => {
      if (params['id']) {
        this.DeptId=params['id']


        this.subDeptSer.setDeptId(this.DeptId);

        this.GetByID(params['id']);
        this.mode = "Edit"
      }
      else {
        this.mode = "Add"
      }
    });


    this.getTeamLeadsNames();
    localStorage.setItem('HrEmployeeCN', "1");
    localStorage.setItem('HrLocationsCN', "1");
    localStorage.setItem('HrAttendaceCN', "1");


  }
  getTeamLeadsNames() {
    this.teamLeadSer.getall().subscribe(
      (d: any) => {
        // console.log(d);  // Add this log
        this.TeamLeadNames =d.teamLeadNames
        console.log('/sdssds');
        
console.log(this.TeamLeadNames);

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


GetByID(id: number){
  this.subDeptSer.GetByID(id).subscribe(
    (d: any) => {
      console.log(d);
      
      this.DeptName = d.data.name;
      this.nameSelected = d.data.team_lead.name;
    }
  );
}

cancel(){
  this.router.navigateByUrl("/HR/HRDepartment");

}

UpdateDepartment(){
  const teamLead = this.TeamLeadNames.find(teamLead => teamLead.team_lead_name === this.nameSelected);
  if (teamLead) {
    const teamLeadId = teamLead.team_lead_id;

    this.subDeptSer.UpdateDept(this.DeptId, this.DeptName, teamLeadId ).subscribe(
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
    this.SaveButton=false;
    Swal.fire({
      text: "No manager found with the selected name",
      confirmButtonText: "OK",
      confirmButtonColor: "#FF7519",

    });
  }
}

}
