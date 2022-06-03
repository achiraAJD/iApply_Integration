USE [LGS_UAT]
GO

/****** Object:  View [dbo].[vwWebLicenceClasses]    Script Date: 3/06/2022 2:19:19 PM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

/******************************************************************************
**		Change History
*******************************************************************************
**		Date:		Author:			Description:
**		--------	--------		-------------------------------------------
**		07/06/2006 	D Hill			Original CR462
**		17/02/2009 	A Grecu			Original 
**		05/03/2013	F Warhurst		Small Venues
*******************************************************************************/
ALTER VIEW [dbo].[vwWebLicenceClasses]
AS
SELECT        LC_ID, LC_Desc, LC_Number, LC_Active, CreationDateTime, CreationUser, LastUpdateDateTime, LastUpdateUser, TimeStamp, LC_SpecialConditions, LC_Limit, LC_InspectionTemplate, LC_Purpose, 
                         LC_ResetNumberDate, LC_LicCond, LC_LicWaivers, LC_LicExempt, LC_Code, LC_ShowOnWeb, LC_Occupational
FROM            dbo.LicenceClasses
WHERE        (LC_ShowOnWeb <> 0) AND (LC_LicCond = 0) AND (LC_Code NOT IN ('511', '519', '520', '522', '523', '525', '530', '540', '541', '542', '550', '560'))
GO


