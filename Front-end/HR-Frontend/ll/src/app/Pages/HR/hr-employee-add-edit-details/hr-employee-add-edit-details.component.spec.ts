import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HrEmployeeAddEditDetailsComponent } from './hr-employee-add-edit-details.component';

describe('HrEmployeeAddEditDetailsComponent', () => {
  let component: HrEmployeeAddEditDetailsComponent;
  let fixture: ComponentFixture<HrEmployeeAddEditDetailsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HrEmployeeAddEditDetailsComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HrEmployeeAddEditDetailsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
