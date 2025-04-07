import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HrSubDepartmentComponent } from './hr-sub-department.component';

describe('HrSubDepartmentComponent', () => {
  let component: HrSubDepartmentComponent;
  let fixture: ComponentFixture<HrSubDepartmentComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HrSubDepartmentComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HrSubDepartmentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
