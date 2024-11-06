import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HrEmployeeDetailsComponent } from './hr-employee-details.component';

describe('HrEmployeeDetailsComponent', () => {
  let component: HrEmployeeDetailsComponent;
  let fixture: ComponentFixture<HrEmployeeDetailsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HrEmployeeDetailsComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HrEmployeeDetailsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
