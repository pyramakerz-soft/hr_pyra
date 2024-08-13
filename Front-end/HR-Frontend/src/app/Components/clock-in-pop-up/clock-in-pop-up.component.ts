import { Component } from '@angular/core';
import { MatDialogRef } from '@angular/material/dialog';

@Component({
  selector: 'app-clock-in-pop-up',
  standalone: true,
  imports: [],
  templateUrl: './clock-in-pop-up.component.html',
  styleUrl: './clock-in-pop-up.component.css'
})
export class ClockInPopUpComponent {
  constructor(public dialogRef: MatDialogRef<ClockInPopUpComponent>) {}

  closeDialog(): void {
    this.dialogRef.close();
  }
}
