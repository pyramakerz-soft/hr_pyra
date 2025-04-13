import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import Swal from 'sweetalert2';
import { SubDepartment } from '../../../Models/sub-department';
import { SubDepartmentService } from '../../../Services/sub-department.service';

@Component({
  selector: 'app-hr-department',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './hr-sub-department.component.html',
  styleUrl: './hr-sub-department.component.css'
})


export class HrSubDepartmentComponent {
  subDepts: SubDepartment[] = []
  DeptId: number  = 0;

AddButton:boolean=false
  constructor(private router: Router, public subDeptSer: SubDepartmentService, private route: ActivatedRoute,


  ) { }

  ngOnInit() {



    this.route.params.subscribe(params => {
        this.DeptId=params['id']
        this.subDeptSer.setDeptId(this.DeptId);

    });
this.GetSubDepartment()

    localStorage.setItem('HrEmployeeCN', "1");
    localStorage.setItem('HrLocationsCN', "1");
    localStorage.setItem('HrAttendaceCN', "1");
    localStorage.setItem('HrAttanceDetailsCN', "1");

  }


  GetSubDepartment() {
    this.subDeptSer.getall(this.DeptId).subscribe(
      (d: any) => {
        this.subDepts = d.data.map((item: any) => SubDepartment.fromJson(item));
      }
    );
  }
  

  deleteSubDepartment(id: number) {
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

        this.subDeptSer.deleteById(id).subscribe(
          (d: any) => {
            this.GetSubDepartment();
          }
        );

      }
    });
  }

  EditSubDepartment( subDeptId: number) {
    this.AddButton = true;
    this.router.navigateByUrl(`/HR/HRSubDepartmentEdit/${this.DeptId}/${subDeptId}`);
  }
  

  NavigateToAddSubDepartment() {
    this.AddButton=true;
    this.router.navigateByUrl("/HR/HRSubDepartmentAdd");
  }

}
