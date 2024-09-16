import { Component } from '@angular/core';
import { ManagersService } from '../../../Services/managers.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Manager } from '../../../Models/manager';
import { DepartmentService } from '../../../Services/department.service';
import { ActivatedRoute, Router } from '@angular/router';
import Swal from 'sweetalert2';

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
  constructor(public managerServ: ManagersService, public departmentServ: DepartmentService, private router: Router, private route: ActivatedRoute) { }


  ngOnInit() {

    this.route.params.subscribe(params => {
      if (params['id']) {
        this.DeptId=params['id']
        this.GetByID(params['id']);
        this.mode = "Edit"
      }
      else {
        this.mode = "Add"
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
    if (this.nameSelected == "" || this.DeptName == "") {
      Swal.fire({
        text: "Faild to create, Data is Required",
        confirmButtonText: "OK",
        confirmButtonColor: "#FF7519",

      });
    }

    else {
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
      this.departmentServ.createDepartment(this.DeptName, ManagerId).subscribe(
        (response: any) => {
          this.router.navigateByUrl("/HR/HRDepartment");

        },
        (error: any) => {
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
      this.DeptName = d.department.name;
      this.nameSelected = d.department.manager_name
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
    this.departmentServ.UpdateDept(this.DeptId, this.DeptName, ManagerId).subscribe(
      (response: any) => {
        this.router.navigateByUrl("/HR/HRDepartment");

      },
      (error: any) => {
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
    Swal.fire({
      text: "No manager found with the selected name",
      confirmButtonText: "OK",
      confirmButtonColor: "#FF7519",

    });
  }
}

}
