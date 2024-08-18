import { Component } from '@angular/core';
import { ImportEmployeeDataPopUpComponent } from '../import-employee-data-pop-up/import-employee-data-pop-up.component';
import { MatDialogRef } from '@angular/material/dialog';

@Component({
  selector: 'app-bounders-pop-up',
  standalone: true,
  imports: [],
  templateUrl: './bounders-pop-up.component.html',
  styleUrl: './bounders-pop-up.component.css'
})
export class BoundersPopUpComponent {
  // constructor(public dialogRef: MatDialogRef<ImportEmployeeDataPopUpComponent>){}
  
  // closeDialog(): void {
  //   this.dialogRef.close();
  // }
}
