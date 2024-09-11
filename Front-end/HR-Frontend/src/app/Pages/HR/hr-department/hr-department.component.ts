import { Component } from '@angular/core';
import { Department } from '../../../Models/department';
import { Router } from '@angular/router';
import { DepartmentService } from '../../../Services/department.service';
import { CommonModule } from '@angular/common';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-hr-department',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './hr-department.component.html',
  styleUrl: './hr-department.component.css'
})
export class HrDepartmentComponent {
  departments: Department[] = []

  constructor(private router: Router, public departmentServ: DepartmentService) { }

  ngOnInit() {
    this.GetAll();
    localStorage.setItem('HrEmployeeCN', "1");
    localStorage.setItem('HrLocationsCN', "1");
    localStorage.setItem('HrAttendaceCN', "1");
    localStorage.setItem('HrAttanceDetailsCN', "1");

  }
  GetAll() {
    this.departmentServ.getall().subscribe(
      (d: any) => {
        this.departments = d.data.departments;
      }
    );
  }

  deleteDepartment(id: number) {
    Swal.fire({
      title: 'Are you sure you want to Delete This Department?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#FF7519',
      cancelButtonColor: '#17253E',
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {

        this.departmentServ.deleteById(id).subscribe(
          (d: any) => {
            this.GetAll();
          }
        );

      }
    });
  }


  EditDepartment(id: number) {
    this.router.navigateByUrl("/HR/HRDepartmentEdit/" + id);

  }

  NavigateToAddDepartment() {
    this.router.navigateByUrl("/HR/HRDepartmentAdd");
  }

}
