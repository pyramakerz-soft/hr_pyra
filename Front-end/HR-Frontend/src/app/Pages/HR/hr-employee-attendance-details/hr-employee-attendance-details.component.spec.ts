import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HrEmployeeAttendanceDetailsComponent } from './hr-employee-attendance-details.component';

describe('HrEmployeeAttendanceDetailsComponent', () => {
  let component: HrEmployeeAttendanceDetailsComponent;
  let fixture: ComponentFixture<HrEmployeeAttendanceDetailsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HrEmployeeAttendanceDetailsComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HrEmployeeAttendanceDetailsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
