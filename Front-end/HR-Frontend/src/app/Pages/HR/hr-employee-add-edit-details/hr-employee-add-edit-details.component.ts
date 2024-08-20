import { Component } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-hr-employee-add-edit-details',
  standalone: true,
  imports: [],
  templateUrl: './hr-employee-add-edit-details.component.html',
  styleUrl: './hr-employee-add-edit-details.component.css'
})
export class HrEmployeeAddEditDetailsComponent {
  EmployeeId:number | null = null
  
  constructor(private route: ActivatedRoute){}
  
  ngOnInit(): void {
    this.route.params.subscribe(params => {
      if (params['EmpId']) {
        this.EmployeeId = +params['EmpId'];
      }
    });
  }
  
  SaveEmployee() {
    console.log("123")
  }
}
