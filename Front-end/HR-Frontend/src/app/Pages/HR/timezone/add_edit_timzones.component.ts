import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import Swal from 'sweetalert2';
import { Timezone } from '../../../Models/timeZone';
import { TimeZoneService } from '../../../Services/timezone.service';

@Component({
  selector: 'app-timezone-add-edit',
  standalone: true,
  imports: [FormsModule, CommonModule],
  templateUrl: './add_edit_timzones.component.html',
  styleUrls: ['./add_edit_timzones.component.css']
})
export class TimezoneAddEditComponent {
  timezoneId: number = 0;
  timezone: Timezone = { id: null, name: '', value: null};
  isEditMode: boolean = false;
  isSaved: boolean = false;
  
  validationErrors: { [key in keyof Timezone]?: string } = {};

  constructor(
    private route: ActivatedRoute,  
    public timezoneService: TimeZoneService, 
    public router: Router
  ) {}

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      if (params['id']) {
        this.timezoneId = +params['id'];
        this.isEditMode = true;
        this.getTimezoneByID(this.timezoneId);
      }
    });
  }
getTimezoneByID(id: number): void {
  this.timezoneService.getTimezoneById(id).subscribe(
    (response: any) => {
      console.log('Fetched timezone:', response);
      
      // Extract timezone from the response structure
      if (response.result === 'true' && response.timezones) {
        this.timezone = response.timezones;
      } else {
        console.error('Invalid response structure:', response);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Invalid timezone data received',
          confirmButtonColor: '#17253E'
        });
      }
    },
    (error) => {
      console.error('Error fetching timezone', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Failed to load timezone data',
        confirmButtonColor: '#17253E'
      });
    }
  );
}

  isFormValid(): boolean {
    let isValid = true;
    this.validationErrors = {};

    if (!this.timezone.name || this.timezone.name.trim().length < 3) {
      this.validationErrors['name'] = 'Timezone name must be at least 3 characters long.';
      isValid = false;
    }

    // Validation for timezone.value
    const valueAsString = this.timezone.value !== null && this.timezone.value !== undefined
                          ? this.timezone.value.toString()
                          : '';

    if (valueAsString.trim() === '') {
      this.validationErrors['value'] = 'Timezone value is required.';
      isValid = false;
    } else if (!/^[+-]?\d+$/.test(valueAsString)) {
      // Updated message for clarity
      this.validationErrors['value'] = 'Timezone value must be a valid integer (e.g., -7, 0, 5).';
      isValid = false;
    } else {
      const numericValue = parseInt(valueAsString, 10);
      // Added range validation
      if (numericValue < -12 || numericValue > 14) {
        this.validationErrors['value'] = 'Timezone value must be an integer between -12 and +14 inclusive.';
        isValid = false;
      }
    }

    return isValid;
  }

  saveTimezone(): void {
    if (this.isFormValid()) {
      this.isSaved = true;
      
      const operation = this.isEditMode 
        ? this.timezoneService.updateTimezone(this.timezone.id!, this.timezone)
        : this.timezoneService.createTimezone(this.timezone);

      operation.subscribe(
        (response) => {
          this.isSaved = false;
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: `Timezone ${this.isEditMode ? 'updated' : 'created'} successfully!`,
            confirmButtonColor: '#17253E'
          }).then(() => {
            this.router.navigateByUrl('/HR/ShowTimezones');
          });
        },
        (error) => {
          this.isSaved = false;
          console.error('Error saving timezone:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: `Failed to ${this.isEditMode ? 'update' : 'create'} timezone`,
            confirmButtonColor: '#17253E'
          });
          
          if (error.error?.errors) {
            this.handleValidationErrors(error.error.errors);
          }
        }
      );
    } else {
      // Added SweetAlert for validation errors
      const errorMessages = Object.values(this.validationErrors).filter(msg => msg).join('<br>');
      Swal.fire({
        icon: 'error',
        title: 'Validation Failed',
        html: errorMessages || 'Please correct the form errors.',
        confirmButtonColor: '#17253E'
      });
    }
  }

  onSubmit(): void {
    this.saveTimezone();
  }

  handleValidationErrors(errors: Record<keyof Timezone, string[]>): void {
    for (const key in errors) {
      if (errors.hasOwnProperty(key)) {
        const field = key as keyof Timezone;
        this.validationErrors[field] = errors[field].join(' ');
      }
    }
  }

  onInputValueChange(field: keyof Timezone, value: any): void {
    (this.timezone as any)[field] = value;
    if (value) {
      this.validationErrors[field] = '';
    }
  }
}