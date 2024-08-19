import { Component, Inject } from '@angular/core';
import { ImportEmployeeDataPopUpComponent } from '../import-employee-data-pop-up/import-employee-data-pop-up.component';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-bounders-pop-up',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './bounders-pop-up.component.html',
  styleUrl: './bounders-pop-up.component.css'
})
export class BoundersPopUpComponent {
  location: string = '';
  mode: string = 'add';

  constructor(public dialogRef: MatDialogRef<ImportEmployeeDataPopUpComponent>,
              @Inject(MAT_DIALOG_DATA) public data: any){
                this.mode = data.mode;
                if (this.mode === 'edit') {
                  this.location = data.location;
                }
              }
  
  closeDialog(): void {
    this.dialogRef.close();
  }
}
